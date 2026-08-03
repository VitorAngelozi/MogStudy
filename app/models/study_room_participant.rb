class StudyRoomParticipant < ApplicationRecord
  belongs_to :study_room
  belongs_to :user
end
