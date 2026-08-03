class ProfilesController < ApplicationController
  before_action :require_authentication, only: :update

  def show
    @profile_user = User.find_by!(username: params[:username])
    @logs_count = @profile_user.daily_logs.count
    @sessions_count = @profile_user.study_sessions.count
    @streak = build_streak(@profile_user.id)
    @friendship_state = build_friendship_state(current_user, @profile_user)
  end

  def update
    user = current_user

    if user.update(profile_params)
      user.profile_photo.attach(params[:profile][:photo]) if params.dig(:profile, :photo).present?
      redirect_to profile_path(user), notice: "Perfil atualizado com sucesso."
    else
      @profile_user = user
      @logs_count = user.daily_logs.count
      @sessions_count = user.study_sessions.count
      @streak = build_streak(user.id)
      @friendship_state = build_friendship_state(current_user, user)
      flash.now[:alert] = user.errors.full_messages.first
      render :show, status: :unprocessable_entity
    end
  end

  private

  def profile_params
    params.require(:profile).permit(:profile_title, :bio)
          .transform_values { |value| value.to_s.strip.presence }
  end

  def build_streak(user_id)
    dates = DailyLog.where(user_id: user_id).pluck(:log_date).map(&:to_date).uniq.sort
    streak = 0
    cursor = Time.zone.today
    cursor -= 1.day unless dates.include?(cursor)

    while dates.include?(cursor)
      streak += 1
      cursor -= 1.day
    end

    streak
  end

  def build_friendship_state(viewer, profile_user)
    return nil if viewer.nil? || viewer.id == profile_user.id

    friendship = Friendship.where(requester_id: viewer.id, addressee_id: profile_user.id)
                           .or(Friendship.where(requester_id: profile_user.id, addressee_id: viewer.id))
                           .first

    return { state: "none", friendship: nil } unless friendship

    state = if friendship.status == Friendship::STATUS_ACCEPTED
      "accepted"
    elsif friendship.requester_id == viewer.id
      "sent"
    else
      "received"
    end

    { state: state, friendship: friendship }
  end
end
