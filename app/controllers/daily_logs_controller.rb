class DailyLogsController < AuthenticatedController
  def create
    data = params.require(:daily_log).permit(:log_date, :title, :content)
    log_date = data[:log_date].present? ? Date.parse(data[:log_date].to_s) : Time.zone.today
    study_minutes = current_user.study_sessions.where(started_at: log_date.all_day).sum(:duration_seconds).to_i / 60

    log = current_user.daily_logs.find_or_initialize_by(log_date: log_date)
    log.update!(
      title: data[:title].to_s.strip,
      content: data[:content].to_s.strip,
      study_minutes: study_minutes
    )

    redirect_to dashboard_path, notice: "Registro diário salvo com sucesso."
  end
end
