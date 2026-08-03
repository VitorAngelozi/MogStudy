class StudySubjectsController < AuthenticatedController
  def index
    study_subjects = current_user.study_subjects.with_attached_photo.to_a
    ordered_subjects = StudySubjectCards.new.sort_by_recent_activity(study_subjects)

    @study_subjects = ordered_subjects
    @subjects = StudySubjectCards.new.build(ordered_subjects)
  end

  def create
    subject = current_user.study_subjects.new
    assign_subject_attributes(subject, study_subject_params)

    if subject.save
      subject.photo.attach(study_subject_params[:photo]) if study_subject_params[:photo].present?
      redirect_to(after_save_path, notice: "Materia criada com sucesso.")
    else
      flash.now[:alert] = subject.errors.full_messages.first
      index
      render :index, status: :unprocessable_entity
    end
  end

  def update
    subject = current_user.study_subjects.find(params[:id])
    assign_subject_attributes(subject, study_subject_params)

    if subject.save
      subject.photo.attach(study_subject_params[:photo]) if study_subject_params[:photo].present?
      redirect_to(after_save_path, notice: "Materia atualizada com sucesso.")
    else
      flash.now[:alert] = subject.errors.full_messages.first
      index
      render :index, status: :unprocessable_entity
    end
  end

  def destroy
    subject = current_user.study_subjects.find(params[:id])

    if subject.study_sessions.where(ended_at: nil).exists?
      redirect_to study_subjects_path, alert: "Finalize a sessao em andamento antes de excluir essa materia."
      return
    end

    subject.photo.purge if subject.photo.attached?
    subject.destroy

    redirect_to study_subjects_path, notice: "Materia excluida com sucesso."
  end

  private

  def study_subject_params
    params.require(:study_subject).permit(:name, :description, :goal_value, :goal_unit, :photo, :return_to)
          .to_h.symbolize_keys
          .transform_values { |value| value.is_a?(String) ? value.strip : value }
  end

  def assign_subject_attributes(subject, params_hash)
    assign_goal(subject, params_hash)
    subject.name = params_hash[:name].to_s.strip
    subject.description = params_hash[:description].presence
  end

  def assign_goal(subject, params_hash = study_subject_params)
    subject.goal_minutes = normalize_goal_minutes(params_hash[:goal_value], params_hash[:goal_unit])
    subject.goal_period = subject.goal_minutes.present? ? "weekly" : nil
  end

  def normalize_goal_minutes(value, unit)
    return nil if value.blank?

    numeric_value = value.to_f
    return nil if numeric_value <= 0

    (unit.to_s == "hours" ? numeric_value * 60 : numeric_value).round.clamp(1, 99_999)
  end

  def after_save_path
    study_subject_params[:return_to] == "subjects" ? study_subjects_path : dashboard_path
  end
end
