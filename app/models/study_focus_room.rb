class StudyFocusRoom < ApplicationRecord
  belongs_to :study_group
  has_many :participations, class_name: "StudyFocusParticipation", dependent: :destroy
  has_many :study_sessions, dependent: :nullify

  scope :active, -> { where(is_active: true) }
end
