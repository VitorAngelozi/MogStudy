module ApplicationHelper
  ICON_PATHS = {
    book: [ [ "M4 19V5a2 2 0 0 1 2-2h11v18H6a2 2 0 0 1-2-2Z", nil ] ],
    clock: [ [ "M12 8v5l3 2", nil ], [ "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z", nil ] ],
    calendar: [ [ "M8 2v4", nil ], [ "M16 2v4", nil ], [ "M3 10h18", nil ], [ "M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z", nil ] ],
    bell: [ [ "M10 18a2 2 0 0 0 4 0", nil ], [ "M15 18H5a1 1 0 0 1-.8-1.6C5.2 15.3 6 14 6 10a6 6 0 0 1 12 0c0 4 0.8 5.3 1.8 6.4A1 1 0 0 1 19 18Z", nil ] ],
    search: [ [ "M21 21l-4.35-4.35", nil ], [ "M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z", nil ] ],
    users: [ [ "M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2", nil ], [ "M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z", nil ], [ "M18 8a3 3 0 1 0-2.9-3.8", nil ] ],
    notes: [ [ "M7 3h10l4 4v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z", nil ], [ "M14 3v5h5", nil ] ],
    target: [ [ "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z", nil ], [ "M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z", nil ], [ "M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z", nil ] ],
    trophy: [ [ "M8 21h8", nil ], [ "M12 17v4", nil ], [ "M7 4h10v4a5 5 0 0 1-10 0V4Z", nil ], [ "M5 6H3a1 1 0 0 0-1 1 5 5 0 0 0 5 5", nil ], [ "M19 6h2a1 1 0 0 1 1 1 5 5 0 0 1-5 5", nil ] ],
    home: [ [ "M3 11.5 12 3l9 8.5", nil ], [ "M5 10.5V21h14v-10.5", nil ] ],
    chart: [ [ "M4 19V5", nil ], [ "M8 19V9", nil ], [ "M12 19V13", nil ], [ "M16 19V7", nil ], [ "M20 19V4", nil ] ],
    settings: [ [ "M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z", nil ], [ "M19.4 15a7.94 7.94 0 0 0 .1-1 7.94 7.94 0 0 0-.1-1l2-1.5-2-3.4-2.3.7a8.4 8.4 0 0 0-1.7-1l-.4-2.4H9l-.4 2.4a8.4 8.4 0 0 0-1.7 1l-2.3-.7-2 3.4 2 1.5a7.94 7.94 0 0 0-.1 1 7.94 7.94 0 0 0 .1 1l-2 1.5 2 3.4 2.3-.7a8.4 8.4 0 0 0 1.7 1l.4 2.4h6.2l.4-2.4a8.4 8.4 0 0 0 1.7-1l2.3.7 2-3.4-2-1.5Z", nil ] ],
    pencil: [ [ "M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z", nil ], [ "M13.5 6.5l4 4", nil ] ],
    trash: [ [ "M3 6h18", nil ], [ "M8 6V4h8v2", nil ], [ "M6 6l1 14h10l1-14", nil ] ],
    upload: [ [ "M12 16V4", nil ], [ "m7 7-7-7-7 7", nil ], [ "M5 20h14", nil ] ],
    plus: [ [ "M12 5v14", nil ], [ "M5 12h14", nil ] ],
    stopwatch: [
      [ "M9 2h6", nil ],
      [ "M12 10v4l2.5 1.5", nil ],
      [ "M9.5 5.2 8 3.7", nil ],
      [ "M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z", nil ]
    ],
    calendar_check: [
      [ "M8 2v4", nil ],
      [ "M16 2v4", nil ],
      [ "M3 10h18", nil ],
      [ "M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z", nil ],
      [ "M8.5 15.5 10.8 17.8 15.5 13.2", nil ]
    ],
    bolt: [
      [ "M13 2 3 14h7l-1 8 12-14h-7l1-6Z", nil ]
    ],
    fire: [
      [ "M12 2c-.7 2.6-2.7 4-4.6 6.2C5.4 10.2 4 12.4 4 15a8 8 0 0 0 16 0c0-2.6-1.4-4.8-3.4-6.8C14.7 6 12.7 4.6 12 2Z", { fill: "currentColor", stroke: "none" } ],
      [ "M12 8.2c-1.1 1.5-2.2 2.4-2.8 3.5-.4.8-.6 1.7-.6 2.4 0 1.9 1.5 3.4 3.4 3.4s3.4-1.5 3.4-3.4c0-.8-.2-1.6-.7-2.4-.6-1-1.6-1.9-2.7-3.5Z", { fill: "currentColor", stroke: "none" } ]
    ],
    medal: [ [ "M7 3h10l-2 6H9L7 3Z", nil ], [ "M12 9a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z", nil ] ],
    sun: [ [ "M12 3v2", nil ], [ "M12 19v2", nil ], [ "M5.6 5.6l1.4 1.4", nil ], [ "M17 17l1.4 1.4", nil ], [ "M3 12h2", nil ], [ "M19 12h2", nil ], [ "M5.6 18.4l1.4-1.4", nil ], [ "M17 7l1.4-1.4", nil ] ],
    moon: [ [ "M20 14.4A7.8 7.8 0 0 1 9.6 4a8 8 0 1 0 10.4 10.4Z", nil ] ]
  }.freeze

  def icon_svg(name, class_name: "icon-svg")
    paths = ICON_PATHS[name.to_sym] || ICON_PATHS[:book]
    content_tag(:svg, class: class_name, viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", "stroke-width": 1.8, "stroke-linecap": "round", "stroke-linejoin": "round") do
      safe_join(paths.map { |path, extra| tag.path(d: path, **(extra || {})) })
    end
  end

  def format_seconds_as_hours(seconds)
    minutes = (seconds.to_i / 60)
    hours = minutes / 60
    remaining = minutes % 60

    return "#{remaining}m" if hours.zero?
    return "#{hours}h" if remaining.zero?

    "#{hours}h#{remaining}"
  end

  def format_minutes_as_hours(minutes)
    minutes = minutes.to_i
    hours = minutes / 60
    remaining = minutes % 60

    return "#{remaining}m" if hours.zero?
    return "#{hours}h" if remaining.zero?

    "#{hours}h#{remaining}"
  end

  def avatar_initial(name)
    name.to_s.strip.first.to_s.upcase.presence || "U"
  end
end
