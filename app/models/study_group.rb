class StudyGroup < ApplicationRecord
  STATUS_ACTIVE = "active"
  STATUS_ARCHIVED = "archived"
  VISIBILITY_FRIENDS = "friends"
  VISIBILITY_PASSWORD = "password"
  VISIBILITY_PUBLIC = "public"

  belongs_to :owner, class_name: "User"
  has_many :members, class_name: "StudyGroupMember", dependent: :destroy
  has_many :focus_rooms, class_name: "StudyFocusRoom", dependent: :destroy
  has_many :study_sessions, dependent: :nullify

  validates :name, :code, :visibility, :status, presence: true
  validates :code, uniqueness: true

  scope :active, -> { where(status: STATUS_ACTIVE) }

  def to_param = code

  def active?
    status == STATUS_ACTIVE
  end

  def visibility_label
    case visibility
    when VISIBILITY_FRIENDS then "Somente amigos"
    when VISIBILITY_PASSWORD then "Privado com senha"
    else "Publico"
    end
  end

  def has_password?
    password_hash.present?
  end

  def password_matches?(password)
    return true if password_hash.blank?

    BCrypt::Password.new(password_hash) == password.to_s
  rescue BCrypt::Errors::InvalidHash
    false
  end
end
