class CirclePost < ApplicationRecord
  belongs_to :user
  has_many :replies, class_name: "CirclePostReply", dependent: :destroy
end
