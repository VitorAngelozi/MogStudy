class ActivityHeatmap
  def build(user_id, end_date = Time.current)
    ended_at = end_date.to_date.beginning_of_day
    started_at = ended_at - 364.days
    grid_start = started_at.beginning_of_week(:monday)
    grid_end = ended_at.end_of_week(:sunday)
    minutes_by_date = minutes_by_date_for(user_id, started_at, ended_at)
    weeks = []

    week_start = grid_start
    while week_start <= grid_end
      days = []
      7.times do |offset|
        date = week_start + offset.days
        date_key = date.to_date.to_s
        is_empty = date < started_at || date > ended_at
        minutes = is_empty ? 0 : minutes_by_date[date_key].to_i

        days << {
          date: date_key,
          minutes: minutes,
          level: contribution_level(minutes),
          is_empty: is_empty,
          label: is_empty ? "" : "#{date.strftime('%d/%m/%Y')} - #{format_minutes_as_hours(minutes)} estudados"
        }
      end

      weeks << { date: week_start.to_date.to_s, days: days }
      week_start += 1.week
    end

    {
      rows: ["Seg", "", "Qua", "", "Sex", "", "Dom"],
      weeks: weeks,
      months: build_month_labels(weeks, started_at, ended_at),
      total_days: 365,
      started_at: started_at.to_date.to_s,
      ended_at: ended_at.to_date.to_s
    }
  end

  private

  def minutes_by_date_for(user_id, started_at, ended_at)
    StudySession.where(user_id: user_id)
                .where.not(ended_at: nil)
                .where(started_at: started_at.beginning_of_day..ended_at.end_of_day)
                .group("DATE(started_at)")
                .sum(:duration_seconds)
                .transform_keys { |date| Date.parse(date.to_s).to_s }
                .transform_values { |seconds| seconds / 60 }
  end

  def build_month_labels(weeks, started_at, ended_at)
    labels = []
    previous_month = nil

    weeks.each_with_index do |week, index|
      visible_days = week[:days].reject { |day| day[:is_empty] }
      month_date = if visible_days.any? { |day| Date.parse(day[:date]).day == 1 }
        Date.parse(visible_days.find { |day| Date.parse(day[:date]).day == 1 }[:date])
      elsif index.zero?
        started_at.to_date
      else
        visible_days.first && Date.parse(visible_days.first[:date])
      end

      month_key = month_date&.strftime("%Y-%m")
      labels << {
        label: month_key && month_key != previous_month ? short_month_label(month_date) : "",
        date: (month_date || ended_at.to_date).to_s
      }
      previous_month = month_key if month_key
    end

    labels
  end

  def short_month_label(date)
    %w[Jan Fev Mar Abr Mai Jun Jul Ago Set Out Nov Dez][date.month - 1]
  end

  def contribution_level(minutes)
    case minutes
    when 120.. then 4
    when 60...120 then 3
    when 30...60 then 2
    when 1...30 then 1
    else 0
    end
  end

  def format_minutes_as_hours(minutes)
    hours = minutes / 60
    remaining = minutes % 60
    return "#{remaining}m" if hours.zero?
    return "#{hours}h" if remaining.zero?

    "#{hours}h#{remaining}"
  end
end
