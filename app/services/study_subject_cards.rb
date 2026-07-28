class StudySubjectCards
  def build(study_subjects)
    max_seconds = [study_subjects.map(&:duration_seconds_total).max.to_i, 1].max
    tones = %w[violet cyan amber emerald indigo]
    icons = %w[book book book book target]

    study_subjects.each_with_index.map do |subject, index|
      seconds = subject.duration_seconds_total.to_i

      {
        id: subject.id,
        name: subject.name,
        description: subject.description,
        photo_url: subject.photo.attached? ? Rails.application.routes.url_helpers.rails_blob_path(subject.photo, only_path: true) : subject.photo_path.present? ? "/storage/#{subject.photo_path}" : nil,
        seconds: seconds,
        hours_label: "#{format_study_seconds_as_hours(seconds)} estudadas",
        goal_minutes: subject.goal_minutes,
        goal_value: subject.goal_minutes.present? ? format_goal_value(subject.goal_minutes) : "",
        goal_unit: subject.goal_minutes.present? && (subject.goal_minutes % 60).zero? ? "hours" : "minutes",
        goal_progress: build_subject_goal_progress(subject, seconds, max_seconds),
        goal_label: build_subject_goal_label(subject),
        recent_at: recent_activity_date(subject)&.to_datetime&.to_s,
        tone: tones[index % tones.length],
        icon: icons[index % icons.length]
      }
    end
  end

  def sort_by_recent_activity(study_subjects)
    study_subjects.sort_by { |subject| -(recent_activity_date(subject)&.to_i || 0) }
  end

  private

  def recent_activity_date(subject)
    [subject.updated_at, subject.created_at, subject.last_studied_at].compact.max
  end

  def build_subject_goal_progress(subject, total_seconds, max_seconds)
    if subject.goal_minutes.blank?
      return total_seconds.positive? ? ((total_seconds.to_f / max_seconds) * 100).round : 0
    end

    period_seconds = subject.duration_seconds_week.to_i
    [((period_seconds / 60.0) / [subject.goal_minutes.to_i, 1].max * 100).round, 100].min
  end

  def build_subject_goal_label(subject)
    return "Sem meta definida" if subject.goal_minutes.blank?

    "Meta: #{format_minutes_as_hours(subject.goal_minutes)} por semana"
  end

  def format_goal_value(minutes)
    minutes % 60 == 0 ? (minutes / 60).to_s : minutes.to_s
  end

  def format_study_seconds_as_hours(seconds)
    minutes = seconds.to_i / 60
    minutes = 1 if seconds.positive? && minutes.zero?
    format_minutes_as_hours(minutes)
  end

  def format_minutes_as_hours(minutes)
    hours = minutes / 60
    remaining = minutes % 60
    return "#{remaining}m" if hours.zero?
    return "#{hours}h" if remaining.zero?

    "#{hours}h#{remaining}"
  end
end
