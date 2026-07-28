class StudyGroupsController < AuthenticatedController
  before_action :set_group, only: %i[show update join leave presence store_focus_room update_focus_room destroy_focus_room show_focus_room start_focus_study stop_focus_study]

  def index
    @join_code = params[:code].to_s.strip
    @search = params[:group_search].to_s.strip

    @groups = current_user.study_group_memberships.includes(study_group: :owner)
                          .joins(:study_group)
                          .order("study_groups.created_at DESC")
                          .map(&:study_group)

    @public_groups = StudyGroup.active.where(visibility: StudyGroup::VISIBILITY_PUBLIC)
                               .where.not(id: @groups.map(&:id))
                               .order(created_at: :desc)
                               .limit(6)

    @search_results = @search.blank? ? [] : search_groups(@search)
    @active_participation = current_user.study_focus_participations.includes(:study_subject, study_focus_room: :study_group)
                                        .where(status: StudyFocusParticipation::STATUS_ACTIVE)
                                        .order(started_at: :desc)
                                        .first
  end

  def new
    @group = StudyGroup.new
  end

  def create
    group = current_user.owned_study_groups.new(group_params)
    group.code = generate_group_code
    group.status = StudyGroup::STATUS_ACTIVE

    if group.visibility == StudyGroup::VISIBILITY_PASSWORD && params.dig(:study_group, :password).present?
      group.password_hash = BCrypt::Password.create(params.dig(:study_group, :password))
    end

    if group.save
      group.members.create!(user: current_user, role: StudyGroupMember::ROLE_OWNER, joined_at: Time.current)
      redirect_to study_group_path(group), notice: "Grupo de estudo criado."
    else
      flash.now[:alert] = group.errors.full_messages.first
      @group = group
      render :new, status: :unprocessable_entity
    end
  end

  def show
    authorize @group, :view?
    load_group_details
  end

  def update
    authorize @group

    if @group.update(group_params_for_update)
      redirect_to study_group_path(@group), notice: "Grupo atualizado."
    else
      load_group_details
      flash.now[:alert] = @group.errors.full_messages.first
      render :show, status: :unprocessable_entity
    end
  end

  def join
    authorize @group
    ensure_password_if_needed

    already_member = membership.present?
    create_membership unless already_member

    redirect_to study_group_path(@group), notice: already_member ? "Você já participa desse grupo." : "Você entrou no grupo."
  rescue StandardError => e
    redirect_to study_group_path(@group), alert: e.message
  end

  def join_by_code
    code = params[:code].to_s.strip.upcase
    @group = StudyGroup.find_by!(code: code)
    authorize @group, :join?
    ensure_password_if_needed
    create_membership unless membership.present?

    redirect_to study_group_path(@group), notice: "Você entrou no grupo."
  rescue ActiveRecord::RecordNotFound
    redirect_to study_groups_path, alert: "Nenhum grupo encontrado com esse código."
  rescue StandardError => e
    redirect_to study_group_path(@group), alert: e.message
  end

  def leave
    authorize @group
    membership&.destroy!
    redirect_to study_groups_path, notice: "Você saiu do grupo."
  end

  def presence
    authorize @group, :view?
    statistics = StudyGroups::StudyGroupStatisticsService.new
    active = statistics.active_participations_for_group(@group)

    # This JSON payload feeds the existing polling widget in the front-end, so
    # keep the shape stable even when the underlying collection changes.
    render json: {
      active_count: active.count,
      seconds_today: statistics.seconds_today_for_group(@group),
      participants: active.map do |participation|
        {
          name: participation.user.display_name,
          avatar: participation.user.display_name.first.to_s.upcase,
          photo_url: participation.user.profile_photo_url,
          subject: participation.study_subject.name,
          room: participation.study_focus_room.name,
          started_at: participation.started_at.iso8601,
          elapsed_seconds: participation.effective_elapsed_seconds,
          is_paused: participation.paused?
        }
      end
    }
  end

  def store_focus_room
    authorize @group, :manage_focus_rooms?

    if @group.focus_rooms.exists?(name: params.dig(:focus_room, :name).to_s.strip)
      redirect_to study_group_path(@group), alert: "Esse grupo já possui uma sala de foco com esse nome."
      return
    end

    room = @group.focus_rooms.create!(focus_room_params.merge(position: (@group.focus_rooms.maximum(:position).to_i + 1)))
    redirect_to study_group_path(@group, room: room.id), notice: "Sala de foco criada."
  end

  def update_focus_room
    room = @group.focus_rooms.find(params[:focus_room_id] || params[:id])
    authorize room, :update?

    if @group.focus_rooms.where(name: focus_room_params[:name]).where.not(id: room.id).exists?
      redirect_to study_group_path(@group, room: room.id), alert: "Esse grupo já possui uma sala de foco com esse nome."
      return
    end

    room.update!(
      name: focus_room_params[:name].presence || room.name,
      description: focus_room_params[:description].presence,
      icon: focus_room_params[:icon].presence || "book"
    )

    redirect_to study_group_path(@group, room: room.id), notice: "Sala de foco atualizada."
  end

  def destroy_focus_room
    room = @group.focus_rooms.find(params[:focus_room_id] || params[:id])
    authorize room, :delete?

    if room.participations.exists? || room.study_sessions.exists?
      room.update!(is_active: false)
      redirect_to study_group_path(@group), notice: "Sala arquivada para preservar o histórico."
    else
      room.destroy!
      redirect_to study_group_path(@group), notice: "Sala removida."
    end
  end

  def show_focus_room
    room = @group.focus_rooms.find(params[:focus_room_id] || params[:id])
    authorize room, :view?
    redirect_to study_group_path(@group, room: room.id)
  end

  def start_focus_study
    room = @group.focus_rooms.find(params[:focus_room_id] || params[:id])
    authorize room, :start?

    # Starting a focus session links the personal subject, the study group and
    # the room so later reports can reconstruct the full history.
    subject = current_user.study_subjects.find(params.dig(:focus_session, :study_subject_id))
    if current_user.study_sessions.where(ended_at: nil).exists? || current_user.study_focus_participations.active.exists?
      redirect_to study_group_path(@group, room: room.id), alert: "Finalize o estudo ativo antes de iniciar outro."
      return
    end

    session = current_user.study_sessions.create!(
      study_subject: subject,
      study_group: @group,
      study_focus_room: room,
      subject: subject.name,
      notes: params.dig(:focus_session, :notes).to_s.strip.presence,
      started_at: Time.current,
      duration_seconds: 0
    )

    current_user.study_focus_participations.create!(
      study_focus_room: room,
      study_session: session,
      study_subject: subject,
      started_at: Time.current,
      status: StudyFocusParticipation::STATUS_ACTIVE
    )

    redirect_to study_group_path(@group, room: room.id), notice: "Estudo iniciado nessa sala."
  rescue ActiveRecord::RecordNotFound
    redirect_to study_group_path(@group, room: room.id), alert: "Escolha uma materia cadastrada no seu perfil."
  end

  def stop_focus_study
    room = @group.focus_rooms.find(params[:focus_room_id] || params[:id])
    participation = current_user.study_focus_participations.active.find_by(study_focus_room_id: room.id)

    unless participation
      head :forbidden
      return
    end

    ended_at = Time.current
    duration_seconds = [ participation.effective_elapsed_seconds, 1 ].max

    participation.study_session.update!(
      ended_at: ended_at,
      paused_at: nil,
      duration_seconds: duration_seconds
    )
    participation.update!(
      ended_at: ended_at,
      paused_at: nil,
      duration_seconds: duration_seconds,
      status: StudyFocusParticipation::STATUS_COMPLETED
    )

    redirect_to study_group_path(@group, room: room.id), notice: "Estudo finalizado e salvo no histórico."
  end

  private

  def set_group
    @group = StudyGroup.find_by!(code: params[:code])
  end

  def load_group_details
    @group = StudyGroup.includes(:owner, members: :user, focus_rooms: { participations: [ :user, :study_subject ] }).find(@group.id)
    @membership = membership
    @can_manage_rooms = @membership&.can_manage_focus_rooms? || false
    @summary = StudyGroups::StudyGroupStatisticsService.new.group_summary(@group)
    @active_participation = current_user.study_focus_participations.includes(:study_subject, study_focus_room: :study_group)
                                       .where(status: StudyFocusParticipation::STATUS_ACTIVE)
                                       .order(started_at: :desc)
                                       .first
    @selected_room = selected_room(@active_participation)
    if @selected_room
      @selected_room = StudyFocusRoom.includes(participations: [ :user, :study_subject ]).find(@selected_room.id)
    end
    @room_summaries = @group.focus_rooms.index_with { |room| StudyGroups::StudyGroupStatisticsService.new.room_summary(room) }
    @subjects = current_user.study_subjects.order(:name).to_a
  end

  def membership
    @group.members.find_by(user_id: current_user.id)
  end

  def create_membership
    @group.members.create!(user: current_user, role: StudyGroupMember::ROLE_MEMBER, joined_at: Time.current)
  end

  def ensure_password_if_needed
    return if @group.visibility != StudyGroup::VISIBILITY_PASSWORD || membership.present?

    # Password-protected groups only create a membership after the submitted
    # password matches the stored hash.
    raise "Informe a senha correta para entrar nesse grupo." unless @group.password_matches?(params[:password])
  end

  def selected_room(active_participation)
    requested_room_id = params[:room].to_i

    if requested_room_id.positive?
      room = @group.focus_rooms.find_by(id: requested_room_id)
      return room if room
    end

    if active_participation&.study_focus_room&.study_group_id == @group.id
      return @group.focus_rooms.find_by(id: active_participation.study_focus_room_id) || active_participation.study_focus_room
    end

    @group.focus_rooms.first
  end

  def search_groups(search)
    normalized = search.to_s.downcase.sub(/\A[#@]/, "")

    StudyGroup.active
              .where("LOWER(name) LIKE :query OR LOWER(code) LIKE :query", query: "%#{normalized}%")
              .where(
                visibility: [ StudyGroup::VISIBILITY_PUBLIC, StudyGroup::VISIBILITY_PASSWORD ]
              )
              .order(created_at: :desc)
              .limit(8)
  end

  def group_params
    params.require(:study_group).permit(:name, :description, :visibility, :password)
          .to_h.symbolize_keys
          .transform_values { |value| value.is_a?(String) ? value.strip : value }
  end

  def group_params_for_update
    params.require(:study_group).permit(:name, :description, :visibility, :password)
          .to_h.symbolize_keys
          .transform_values { |value| value.is_a?(String) ? value.strip : value }
          .tap do |attrs|
      attrs[:password_hash] = if attrs[:visibility] == StudyGroup::VISIBILITY_PASSWORD && attrs[:password].present?
        BCrypt::Password.create(attrs[:password])
      elsif attrs[:visibility] != StudyGroup::VISIBILITY_PASSWORD
        nil
      else
        @group.password_hash
      end
      attrs.delete(:password)
    end
  end

  def focus_room_params
    params.require(:focus_room).permit(:name, :description, :icon)
          .to_h.symbolize_keys
          .transform_values { |value| value.is_a?(String) ? value.strip : value }
  end

  def generate_group_code
    loop do
      code = SecureRandom.alphanumeric(8).upcase
      return code unless StudyGroup.exists?(code: code)
    end
  end
end
