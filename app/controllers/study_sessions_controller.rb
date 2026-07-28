class StudySessionsController < AuthenticatedController
  def create
    subject_name = params.dig(:study_session, :study_subject_name).to_s.strip
    subject_id = params.dig(:study_session, :study_subject_id).presence
    notes = params.dig(:study_session, :notes).to_s.strip.presence

    study_subject = current_user.study_subjects.find_by(id: subject_id) || current_user.study_subjects.find_by(name: subject_name)

    unless study_subject
      redirect_back fallback_location: dashboard_path, alert: "Escolha uma materia cadastrada antes de iniciar."
      return
    end

    if current_user.study_sessions.where(ended_at: nil).exists?
      redirect_back fallback_location: dashboard_path, alert: "Voce ja tem uma sessao em andamento."
      return
    end

    current_user.study_sessions.create!(
      study_subject: study_subject,
      subject: study_subject.name,
      notes: notes,
      started_at: Time.current,
      duration_seconds: 0
    )

    redirect_to dashboard_path, notice: "Sessão iniciada com sucesso."
  end

  def pause
    session = current_user.study_sessions.find(params[:id])
    return redirect_back fallback_location: dashboard_path if session.ended_at.present? || session.paused_at.present?

    session.update!(paused_at: Time.current)
    session.study_focus_participations.active.update_all(paused_at: Time.current, updated_at: Time.current)
    redirect_back fallback_location: dashboard_path, notice: "Cronometro pausado."
  end

  def resume
    session = current_user.study_sessions.find(params[:id])
    return redirect_back fallback_location: dashboard_path if session.ended_at.present? || session.paused_at.blank?

    resumed_at = Time.current
    pause_seconds = (resumed_at - session.paused_at).round
    session.update!(paused_at: nil, paused_seconds: session.paused_seconds.to_i + pause_seconds)
    session.study_focus_participations.active.update_all(paused_at: nil, paused_seconds: session.paused_seconds, updated_at: Time.current)
    redirect_back fallback_location: dashboard_path, notice: "Cronometro retomado."
  end

  def stop
    session = current_user.study_sessions.find(params[:id])

    if session.ended_at.present?
      redirect_back fallback_location: dashboard_path, alert: "Essa sessão já foi encerrada."
      return
    end

    ended_at = Time.current
    duration_seconds = [session.effective_elapsed_seconds, 1].max
    session.update!(ended_at: ended_at, paused_at: nil, duration_seconds: duration_seconds)

    session.study_focus_participations.active.update_all(
      ended_at: ended_at,
      paused_at: nil,
      paused_seconds: session.paused_seconds,
      duration_seconds: duration_seconds,
      status: StudyFocusParticipation::STATUS_COMPLETED,
      updated_at: Time.current
    )

    redirect_to dashboard_path, notice: "Sessão encerrada e salva no histórico."
  end
end
