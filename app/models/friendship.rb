class Friendship < ApplicationRecord
  STATUS_ACCEPTED = "accepted"
  STATUS_PENDING = "pending"

  belongs_to :requester, class_name: "User"
  belongs_to :addressee, class_name: "User"

  scope :pending, -> { where(status: STATUS_PENDING) }
  scope :accepted, -> { where(status: STATUS_ACCEPTED) }

  def involves?(user_id)
    requester_id == user_id || addressee_id == user_id
  end
end
