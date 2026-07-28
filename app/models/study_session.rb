class StudySession < ApplicationRecord
  belongs_to :user
  belongs_to :study_subject, optional: true
  belongs_to :study_group, optional: true
  belongs_to :study_focus_room, optional: true

  has_many :study_focus_participations, dependent: :nullify

  def duration_label
    Time.at(duration_seconds.to_i).utc.strftime("%H:%M:%S")
  end

  # Elapsed time keeps subtracting paused windows so the timer stays correct
  # even when a session is paused and resumed multiple times.
  def effective_elapsed_seconds
    end_time = ended_at || paused_at || Time.current
    paused_total = paused_seconds.to_i
    [ (end_time.to_i - started_at.to_i) - paused_total, 0 ].max
  end

  def paused?
    ended_at.blank? && paused_at.present?
  end
end
