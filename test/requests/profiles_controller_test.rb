require "test_helper"

class ProfilesControllerTest < ActionDispatch::IntegrationTest
  test "shows and updates the current profile" do
    user = User.create!(
      username: "profileuser",
      display_name: "Profile User",
      email: "profileuser@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(user)

    get profile_path(user)
    assert_response :success
    assert_match "Profile User", response.body

    put update_profile_path, params: {
      profile: {
        profile_title: "New profile title",
        bio: "Updated bio"
      }
    }

    assert_redirected_to profile_path(user)
    assert_equal "New profile title", user.reload.profile_title
    assert_equal "Updated bio", user.bio
  end
end
