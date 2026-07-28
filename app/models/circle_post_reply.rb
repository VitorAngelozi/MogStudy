class CirclePostReply < ApplicationRecord
  belongs_to :circle_post
  belongs_to :user
end
