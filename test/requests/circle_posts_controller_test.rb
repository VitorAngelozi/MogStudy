require "test_helper"

class CirclePostsControllerTest < ActionDispatch::IntegrationTest
  test "creates posts and replies inside the circle" do
    author = User.create!(
      username: "author",
      display_name: "Author",
      email: "author@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    friend = User.create!(
      username: "friendcircle",
      display_name: "Friend Circle",
      email: "friendcircle@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    Friendship.create!(requester: author, addressee: friend, status: Friendship::STATUS_ACCEPTED)

    sign_in_as(author)

    assert_difference -> { CirclePost.count }, 1 do
      post circle_posts_path, params: { circle_post: { title: "Weekly update", body: "Kept my streak" } }
    end

    post_record = CirclePost.find_by!(title: "Weekly update")

    sign_in_as(friend)

    assert_difference -> { CirclePostReply.count }, 1 do
      post circle_post_replies_path(circle_post_id: post_record.id), params: { reply: { body: "Great job!" } }
    end
  end
end
