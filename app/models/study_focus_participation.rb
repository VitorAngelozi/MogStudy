class StudyFocusParticipation < ApplicationRecord
  STATUS_ACTIVE = "active"
  STATUS_CANCELLED = "cancelled"
  STATUS_COMPLETED = "completed"

  belongs_to :study_focus_room
  belongs_to :study_session, optional: true
  belongs_to :user
  belongs_to :study_subject

  scope :active, -> { where(status: STATUS_ACTIVE) }

  def duration_label
    Time.at(duration_seconds.to_i).utc.strftime("%H:%M:%S")
  end

  # Focus-room participation mirrors the same elapsed-time logic as study
  # sessions so the public presence JSON and the timer widget stay in sync.
  def effective_elapsed_seconds
    end_time = ended_at || paused_at || Time.current
    paused_total = paused_seconds.to_i
    [ (end_time.to_i - started_at.to_i) - paused_total, 0 ].max
  end

  def paused?
    status == STATUS_ACTIVE && paused_at.present?
  end
end
