class StudyFocusRoomPolicy < ApplicationPolicy
  def view?
    record.study_group.members.exists?(user_id: user&.id)
  end

  def update?
    can_manage?
  end

  def delete?
    can_manage?
  end

  def start?
    record.is_active && record.study_group.active? && record.study_group.members.exists?(user_id: user&.id)
  end

  private

  def can_manage?
    record.study_group.members.where(user_id: user&.id).where(role: [ StudyGroupMember::ROLE_OWNER, StudyGroupMember::ROLE_ADMIN ]).exists?
  end
end
