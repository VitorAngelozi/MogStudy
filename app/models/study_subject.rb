class StudySubject < ApplicationRecord
  has_one_attached :photo

  belongs_to :user
  has_many :study_sessions, dependent: :nullify
  has_many :study_focus_participations, dependent: :nullify

  validates :name, presence: true, length: { maximum: 50 }, uniqueness: { scope: :user_id }

  def duration_seconds_total
    finished_sessions.sum(:duration_seconds).to_i
  end

  def duration_seconds_today
    finished_sessions.where(started_at: Time.zone.today.all_day).sum(:duration_seconds).to_i
  end

  def duration_seconds_week
    finished_sessions.where(started_at: Time.zone.now.beginning_of_week..Time.zone.now.end_of_week).sum(:duration_seconds).to_i
  end

  def last_studied_at
    finished_sessions.maximum(:started_at)
  end

  private

  def finished_sessions
    study_sessions.where.not(ended_at: nil)
  end
end
