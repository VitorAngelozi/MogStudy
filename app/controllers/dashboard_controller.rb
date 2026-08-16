class DashboardController < AuthenticatedController
  def index
    user = current_user
    today = Time.zone.today

    @current_session = user.study_sessions.where(ended_at: nil).order(started_at: :desc).first
    recent_sessions = user.study_sessions.order(started_at: :desc).limit(6).to_a
    recent_logs = user.daily_logs.order(log_date: :desc, created_at: :desc).limit(7).to_a
    @today_log = user.daily_logs.find_by(log_date: today)
    study_subjects = user.study_subjects.with_attached_photo.to_a
    study_dates = completed_study_dates(user)

    totals = {
      seconds_today: user.study_sessions.where.not(ended_at: nil).where(started_at: today.all_day).sum(:duration_seconds).to_i,
      seconds_total: user.study_sessions.where.not(ended_at: nil).sum(:duration_seconds).to_i,
      sessions_total: user.study_sessions.count,
      completed_sessions_total: user.study_sessions.where.not(ended_at: nil).count,
      logs_total: user.daily_logs.count
    }

    subject_cards = StudySubjectCards.new
    @study_subjects = study_subjects
    @subjects = subject_cards.build(subject_cards.sort_by_recent_activity(study_subjects).first(3))
    @heatmap = ActivityHeatmap.new.build(user.id)
    @goal = build_goal(study_subjects)
    @profile = build_profile_summary(user, totals)
    @recent_activity = build_recent_activity(user, recent_sessions, recent_logs)
    @circle = build_circle(user)
    @study_groups = build_study_groups_summary(user)
    @friend_notifications = build_friend_notifications(user)
    @friend_search = build_friend_search(params[:friend_search], user)
    @sidebar_items = build_sidebar_items(user)
    @streak = build_streak(user.id)
    @study_streak = build_consecutive_day_streak(study_dates)
    @achievements = build_achievements(totals, @streak, @goal)
    @metrics = build_metrics(totals, study_dates.count, @study_streak, @goal)
    @greeting = greeting_for_hour(Time.zone.now.hour)
    @hero_subtitle = @current_session ? "Pronto para continuar a sessao em andamento?" : "Pronto para mais uma sessao de foco?"
    # The live timer must continue from persisted study time so pause/resume
    # cycles do not reset the visible elapsed counter.
    current_session_base_seconds = @current_session ? finished_seconds_today_for_current_subject(user, @current_session, today) : 0
    timer_elapsed_seconds = @current_session ? current_session_base_seconds + @current_session.effective_elapsed_seconds : totals[:seconds_today]

    @timer = {
      state: @current_session&.paused? ? "paused" : (@current_session ? "running" : "idle"),
      subject: @current_session&.subject.presence || "Total estudado hoje",
      started_at: @current_session&.started_at&.iso8601,
      rendered_at: Time.current.iso8601,
      base_seconds: current_session_base_seconds,
      elapsed_seconds: timer_elapsed_seconds,
      display: format_timer(timer_elapsed_seconds)
    }

    @totals = {
      minutes_today: totals[:seconds_today] / 60,
      minutes_total: totals[:seconds_total] / 60,
      seconds_today: totals[:seconds_today],
      seconds_total: totals[:seconds_total],
      sessions_total: totals[:sessions_total],
      logs_total: totals[:logs_total],
      hours_total_label: format_seconds_as_hours(totals[:seconds_total]),
      today_label: format_seconds_as_hours(totals[:seconds_today])
    }
  end

  def friend_search
    query = normalize_friend_search_query(params[:friend_search].to_s)

    render json: {
      query: query[:original],
      has_search: query[:normalized].present?,
      results: query[:normalized].blank? ? [] : build_friend_search_results(current_user, query[:normalized]).map { |result| friend_search_result_for_json(result) }
    }
  end

  private

  def build_profile_summary(user, totals)
    experience = ((totals[:seconds_total] / 60) * 8) + (totals[:sessions_total] * 35) + (totals[:logs_total] * 25)
    level = [ experience / 500 + 1, 1 ].max
    xp_current = experience % 500

    {
      avatar: helpers.avatar_initial(user.display_name),
      photo_url: user.profile_photo_url,
      display_name: user.display_name,
      title: user.profile_title,
      username: user.username,
      bio: user.bio.presence || "Sem bio ainda. Adicione uma descricao no perfil.",
      level: level,
      xp: experience,
      xp_current: xp_current,
      xp_goal: 500,
      xp_percent: [ (xp_current.to_f / 500 * 100).round, 100 ].min
    }
  end

  def build_goal(study_subjects)
    subjects_with_goals = study_subjects.select { |subject| subject.goal_minutes.to_i.positive? }
    target_minutes = subjects_with_goals.sum { |subject| subject.goal_minutes.to_i }
    done_seconds = subjects_with_goals.sum { |subject| subject.duration_seconds_week.to_i }
    target_seconds = target_minutes * 60
    remaining_seconds = [ target_seconds - done_seconds, 0 ].max
    progress = target_seconds.positive? ? [ (done_seconds.to_f / target_seconds * 100).round, 100 ].min : 0

    {
      title: "Metas semanais",
      has_goal: target_minutes.positive?,
      done_minutes: done_seconds / 60,
      target_minutes: target_minutes,
      remaining_minutes: remaining_seconds / 60,
      progress: progress,
      done_label: format_seconds_as_hours(done_seconds),
      target_label: format_minutes_as_hours(target_minutes),
      remaining_label: format_seconds_as_hours(remaining_seconds),
      subjects_count: subjects_with_goals.count,
      bars: [ 2, 4, 6, 7, 10, 14, 8, 11, 15, 9, 12, 16, 7, 5, 4, 10, 13, 6, 9, 12, 11, 7, 5, 3 ]
    }
  end

  def build_metrics(totals, study_days_total, study_streak, goal)
    [
      { label: "Horas estudadas", value: format_seconds_as_hours(totals[:seconds_total]), icon: "stopwatch", tone: "violet", subtext: "tempo acumulado" },
      { label: "Dias de estudo", value: study_days_total, icon: "calendar_check", tone: "emerald", subtext: "dias com estudo" },
      { label: "Dias seguidos", value: study_streak, icon: "bolt", tone: "cyan", subtext: "sequencia atual" },
      { label: "Meta semanal", value: goal[:has_goal] ? "#{goal[:progress]}%" : "0%", icon: "target", tone: "amber", subtext: "ritmo consistente" }
    ]
  end

  def build_sidebar_items(user)
    [
      { label: "Inicio", href: "#inicio", icon: "home", active: true },
      { label: "Materias", href: study_subjects_path, icon: "book", active: false },
      { label: "Grupos de estudo", href: study_groups_path, icon: "users", active: false },
      { label: "Anotacoes", href: "#anotacoes", icon: "notes", active: false },
      { label: "Metas", href: "#metas", icon: "target", active: false },
      { label: "Conquistas", href: "#conquistas", icon: "trophy", active: false },
      { label: "Amigos", href: "#amigos", icon: "users", active: false },
      { label: "Ranking", href: "#ranking", icon: "chart", active: false },
      { label: "Configuracoes", href: profile_path(user), icon: "settings", active: false }
    ]
  end

  # Build the activity feed only from persisted records. When there is no
  # actual activity yet, the view renders an empty state instead of sample data.
  def build_recent_activity(user, recent_sessions, recent_logs)
    items = []

    if recent_logs.any?
      log = recent_logs.first
      items << {
        sort_at: log.created_at || log.updated_at || log.log_date&.to_time,
        avatar: helpers.avatar_initial(user.display_name),
        title: "Diario salvo: #{log.title}",
        detail: log.content.to_s.truncate(48),
        when: log.created_at ? helpers.time_ago_in_words(log.created_at) + " atras" : helpers.time_ago_in_words(log.log_date) + " atras",
        accent: "violet"
      }
    end

    recent_sessions.first(2).each do |session|
      items << {
        sort_at: session.started_at,
        avatar: "S",
        title: session.ended_at ? "Sessao finalizada em #{session.subject}" : "Sessao em andamento em #{session.subject}",
        detail: "#{format_timer(session.duration_seconds)} | #{session.notes.presence || 'sem observacoes'}",
        when: helpers.time_ago_in_words(session.started_at) + " atras",
        accent: "emerald"
      }
    end

    items.sort_by { |item| -(item[:sort_at]&.to_i || 0) }
         .map { |item| item.except(:sort_at) }
         .first(4)
  end

  def build_study_groups_summary(user)
    groups = StudyGroup.includes(:members, focus_rooms: :participations)
                       .where(id: StudyGroupMember.where(user_id: user.id).select(:study_group_id))
                       .order(created_at: :desc)
                       .limit(3)

    active_participation = user.study_focus_participations.includes(:study_subject, study_focus_room: :study_group)
                               .where(status: StudyFocusParticipation::STATUS_ACTIVE)
                               .order(started_at: :desc)
                               .first

    statistics = StudyGroups::StudyGroupStatisticsService.new

    {
      groups: groups.map do |group|
        {
          model: group,
          active_count: group.focus_rooms.sum { |room| room.participations.active.count },
          seconds_today: statistics.seconds_today_for_group(group)
        }
      end,
      active_participation: active_participation
    }
  end

  def build_circle(user)
    friend_ids = user.accepted_friend_ids.uniq
    circle_user_ids = (friend_ids + [ user.id ]).uniq

    posts = CirclePost.includes(:user, replies: :user).where(user_id: circle_user_ids).order(created_at: :desc).limit(5)
    friend_sessions = StudySession.includes(:user).where(user_id: friend_ids).order(started_at: :desc).limit(5)

    feed = posts.map { |post| { type: "post", sort_at: post.created_at, post: post } } +
           friend_sessions.map { |session| { type: "session", sort_at: session.started_at, session: session } }

    { friend_ids: friend_ids, feed: feed.sort_by { |item| -(item[:sort_at]&.to_i || 0) }.first(8) }
  end

  def build_friend_notifications(user)
    pending_received = Friendship.includes(:requester).where(addressee_id: user.id, status: Friendship::STATUS_PENDING).order(created_at: :desc).limit(5)
    accepted_sent = Friendship.includes(:addressee).where(requester_id: user.id, status: Friendship::STATUS_ACCEPTED).order(created_at: :desc).limit(5)

    {
      pending_received: pending_received,
      accepted_sent: accepted_sent,
      count: pending_received.count + accepted_sent.count
    }
  end

  def build_friend_search(search, user)
    query = normalize_friend_search_query(search.to_s)

    return { query: query[:original], has_search: false, results: [] } if query[:normalized].blank?

    { query: query[:original], has_search: true, results: build_friend_search_results(user, query[:normalized]) }
  end

  def normalize_friend_search_query(search)
    original = search.to_s.strip
    {
      original: original,
      normalized: original.sub(/\A@/, "").squeeze(" ").strip
    }
  end

  def build_friend_search_results(user, normalized)
    candidates = User.where.not(id: user.id)
                     .where("username LIKE :query OR display_name LIKE :like_query", query: "#{normalized}%", like_query: "%#{normalized}%")
                     .order(Arel.sql("CASE WHEN username = #{User.connection.quote(normalized)} THEN 0 WHEN username LIKE #{User.connection.quote("#{normalized}%")} THEN 1 ELSE 2 END"))
                     .order(:username)
                     .limit(6)

    candidates.map do |candidate|
      {
        user: candidate,
        avatar: helpers.avatar_initial(candidate.display_name),
        photo_url: candidate.profile_photo_url,
        friendship: friendship_state_between(user, candidate)
      }
    end
  end

  def friend_search_result_for_json(result)
    candidate = result[:user]
    friendship = result[:friendship]
    friendship_record = friendship[:friendship]

    {
      display_name: candidate.display_name,
      username: candidate.username,
      profile_url: profile_path(candidate),
      photo_url: result[:photo_url],
      avatar: result[:avatar],
      friendship: {
        state: friendship[:state],
        store_url: friendship[:state] == "none" ? friendships_path(user_id: candidate.id) : nil,
        accept_url: friendship[:state] == "received" ? friendship_record && accept_friendship_path(friendship_record) : nil,
        destroy_url: %w[sent accepted].include?(friendship[:state]) ? friendship_record && friendship_path(friendship_record) : nil
      }
    }
  end

  def friendship_state_between(viewer, candidate)
    friendship = Friendship.where(requester_id: viewer.id, addressee_id: candidate.id)
                           .or(Friendship.where(requester_id: candidate.id, addressee_id: viewer.id))
                           .first

    return { state: "none", friendship: nil } unless friendship

  # Determine the friendship state from the perspective of the viewer.
  
    state = if friendship.status == Friendship::STATUS_ACCEPTED
      "accepted"
    elsif friendship.requester_id == viewer.id
      "sent"
    else
      "received"
    end

    { state: state, friendship: friendship }

  end
  
  # Calculate the total finished seconds for the current subject today, considering the current session and any completed sessions for the same subject.
  def finished_seconds_today_for_current_subject(user, current_session, today)
    scope = user.study_sessions.where.not(ended_at: nil).where(started_at: today.all_day)
    scope = if current_session.study_subject_id.present?
      scope.where(study_subject_id: current_session.study_subject_id)
    else
      scope.where(subject: current_session.subject)
    end

    scope.sum(:duration_seconds).to_i
  end

  # Achievements are derived from real study data only. If the user has not
  # reached any meaningful milestone yet, the view will show an empty state.
  def build_achievements(totals, streak, goal)
    achievements = []

    achievements << {
      image: "badges/badge_firstStudy.png",
      title: "Primeiro estudo registrado",
      detail: "Seu primeiro registro de estudo entrou no historico.",
      when: "agora",
      tone: "gold"
    } if totals[:completed_sessions_total].positive?

    achievements << {
      icon: "clock",
      title: "#{format_seconds_as_hours(totals[:seconds_total])} estudadas",
      detail: "Tempo total acumulado nos registros concluídos.",
      when: "agora",
      tone: "violet"
    } if totals[:seconds_total].positive?

    achievements << {
      icon: "fire",
      title: "#{streak} dias consecutivos",
      detail: "Sequencia atual baseada nos diarios salvos.",
      when: "agora",
      tone: "fire"
    } if streak.positive?

    if goal[:has_goal]
      achievements << {
        icon: "target",
        title: "#{goal[:progress]}% da meta semanal",
        detail: "Progresso real das materias com meta.",
        when: "agora",
        tone: "amber"
      }
    elsif totals[:completed_sessions_total].positive?
      achievements << {
        icon: "calendar",
        title: "#{totals[:completed_sessions_total]} sessoes fechadas",
        detail: "Historico real de sessoes concluidas.",
        when: "agora",
        tone: "emerald"
      }
    end

    achievements.first(4)
  end

  def greeting_for_hour(hour)
    case hour
    when 0...12 then "Bom dia"
    when 12...18 then "Boa tarde"
    else "Boa noite"
    end
  end

  def build_streak(user_id)
    dates = DailyLog.where(user_id: user_id).pluck(:log_date).map(&:to_date).uniq
    build_consecutive_day_streak(dates)
  end

  def completed_study_dates(user)
    user.study_sessions.where.not(ended_at: nil).pluck(:started_at).map(&:to_date).uniq.sort
  end

  def build_consecutive_day_streak(dates)
    dates = dates.sort
    streak = 0
    cursor = Time.zone.today
    cursor -= 1.day unless dates.include?(cursor)

    while dates.include?(cursor)
      streak += 1
      cursor -= 1.day
    end

    streak
  end

  def format_minutes_as_hours(minutes)
    hours = minutes.to_i / 60
    remaining = minutes.to_i % 60
    return "#{remaining}m" if hours.zero?
    return "#{hours}h" if remaining.zero?

    "#{hours}h#{remaining}"
  end

  def format_seconds_as_hours(seconds)
    format_minutes_as_hours(seconds.to_i / 60)
  end

  def format_timer(seconds)
    Time.at(seconds.to_i).utc.strftime("%H:%M:%S")
  end
end
