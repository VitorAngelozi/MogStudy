class StudyRoom < ApplicationRecord
  VISIBILITY_FRIENDS = "friends"
  VISIBILITY_PUBLIC = "public"

  belongs_to :owner, class_name: "User"
  has_many :participants, class_name: "StudyRoomParticipant", dependent: :destroy

  def to_param = code
end
