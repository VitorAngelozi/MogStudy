module StudyGroups
  class StudyGroupStatisticsService
    def group_summary(group)
      {
        members_count: group.members.count,
        active_count: active_participations_for_group(group).count,
        seconds_today: seconds_today_for_group(group)
      }
    end

    def room_summary(room)
      {
        active_count: room.participations.active.count,
        seconds_today: seconds_today_for_room(room)
      }
    end

    def active_participations_for_group(group)
      StudyFocusParticipation.includes(:user, :study_subject, :study_focus_room)
                             .where(status: StudyFocusParticipation::STATUS_ACTIVE)
                             .joins(:study_focus_room)
                             .where(study_focus_rooms: { study_group_id: group.id })
                             .order(started_at: :desc)
    end

    def seconds_today_for_group(group)
      seconds_for_query(StudySession.where(study_group_id: group.id))
    end

    def seconds_today_for_room(room)
      seconds_for_query(StudySession.where(study_focus_room_id: room.id))
    end

    private

    # This query combines finished sessions and currently running sessions so
    # the dashboard can show a stable "studied today" total.
    def seconds_for_query(query)
      today = Time.zone.today
      finished = query.where.not(ended_at: nil).where(started_at: today.all_day).sum(:duration_seconds).to_i
      active = query.where(ended_at: nil).where(started_at: today.all_day).to_a.sum(&:effective_elapsed_seconds)
      finished + active
    end
  end
end
