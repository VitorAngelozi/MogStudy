class StudyGroupMember < ApplicationRecord
  ROLE_ADMIN = "admin"
  ROLE_MEMBER = "member"
  ROLE_MODERATOR = "moderator"
  ROLE_OWNER = "owner"

  belongs_to :study_group
  belongs_to :user

  validates :role, presence: true

  # Only owners and admins may manage focus rooms inside a group.
  def can_manage_focus_rooms?
    [ ROLE_OWNER, ROLE_ADMIN ].include?(role)
  end
end
