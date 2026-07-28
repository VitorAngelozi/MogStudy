class StudyGroupPolicy < ApplicationPolicy
  def show?
    view?
  end

  def view?
    record.visibility == StudyGroup::VISIBILITY_PUBLIC || member? || user&.is_circle_member_with?(record.owner)
  end

  def update?
    member_role == StudyGroupMember::ROLE_OWNER
  end

  def join?
    return false unless record.active?
    return true if member?

    case record.visibility
    when StudyGroup::VISIBILITY_PUBLIC, StudyGroup::VISIBILITY_PASSWORD then true
    when StudyGroup::VISIBILITY_FRIENDS then user&.is_circle_member_with?(record.owner)
    else false
    end
  end

  def leave?
    member? && member_role != StudyGroupMember::ROLE_OWNER
  end

  def manage_focus_rooms?
    [StudyGroupMember::ROLE_OWNER, StudyGroupMember::ROLE_ADMIN].include?(member_role)
  end

  def start_focus_study?
    record.active? && member?
  end

  private

  def member?
    record.members.exists?(user_id: user&.id)
  end

  def member_role
    record.members.where(user_id: user&.id).pick(:role)
  end
end
