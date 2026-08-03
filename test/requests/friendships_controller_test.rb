require "test_helper"

class FriendshipsControllerTest < ActionDispatch::IntegrationTest
  test "creates accepts and destroys friendships" do
    requester = User.create!(
      username: "requester",
      display_name: "Requester",
      email: "requester@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    addressee = User.create!(
      username: "addressee",
      display_name: "Addressee",
      email: "addressee@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(requester)

    assert_difference -> { Friendship.count }, 1 do
      post friendships_path(user_id: addressee.id)
    end

    friendship = Friendship.find_by!(requester: requester, addressee: addressee)
    assert_equal Friendship::STATUS_PENDING, friendship.status

    assert_difference -> { Friendship.count }, -1 do
      delete friendship_path(friendship)
    end

    sign_in_as(addressee)
    friendship = Friendship.create!(requester: requester, addressee: addressee, status: Friendship::STATUS_PENDING)

    post accept_friendship_path(friendship)

    assert_equal Friendship::STATUS_ACCEPTED, friendship.reload.status
  end
end
