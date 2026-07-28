class User < ApplicationRecord
  has_secure_password

  has_one_attached :profile_photo

  has_many :study_sessions, dependent: :destroy
  has_many :owned_study_groups, class_name: "StudyGroup", foreign_key: :owner_id, dependent: :destroy
  has_many :owned_study_rooms, class_name: "StudyRoom", foreign_key: :owner_id, dependent: :destroy
  has_many :study_group_memberships, class_name: "StudyGroupMember", dependent: :destroy
  has_many :study_focus_participations, dependent: :destroy
  has_many :study_room_participations, class_name: "StudyRoomParticipant", dependent: :destroy
  has_many :study_subjects, dependent: :destroy
  has_many :daily_logs, dependent: :destroy
  has_many :sent_friendships, class_name: "Friendship", foreign_key: :requester_id, dependent: :destroy
  has_many :received_friendships, class_name: "Friendship", foreign_key: :addressee_id, dependent: :destroy
  has_many :circle_posts, dependent: :destroy
  has_many :circle_post_replies, dependent: :destroy

  validates :username, presence: true, uniqueness: true, length: { minimum: 3, maximum: 30 }, format: { with: /\A[a-zA-Z0-9_-]+\z/ }
  validates :email, presence: true, uniqueness: true

  def to_param = username

  def display_name
    self[:display_name].presence || username
  end

  def profile_title
    self[:profile_title].presence || display_name
  end

  def profile_photo_url
    if profile_photo.attached?
      Rails.application.routes.url_helpers.rails_blob_path(profile_photo, only_path: true)
    elsif profile_photo_path.present?
      "/storage/#{profile_photo_path}"
    end
  end

  def accepted_friend_ids
    (sent_friendships.where(status: Friendship::STATUS_ACCEPTED).pluck(:addressee_id) +
      received_friendships.where(status: Friendship::STATUS_ACCEPTED).pluck(:requester_id)).uniq
  end

  def is_circle_member_with?(other_user)
    return true if id == other_user.id

    Friendship.accepted.where(
      "(requester_id = :a AND addressee_id = :b) OR (requester_id = :b AND addressee_id = :a)",
      a: id, b: other_user.id
    ).exists?
  end
end
