require "test_helper"

class AuthControllerTest < ActionDispatch::IntegrationTest
  test "registers a user and logs in" do
    assert_difference -> { User.count }, 1 do
      post register_path, params: {
        auth: {
          username: "newstudent",
          display_name: "New Student",
          email: "newstudent@example.com",
          password: "password123",
          password_confirmation: "password123",
          bio: "Focus mode"
        }
      }
    end

    assert_redirected_to dashboard_path
    follow_redirect!
    assert_response :success
    assert_match "New Student", response.body
  end

  test "rejects invalid login" do
    user = User.create!(
      username: "alice",
      display_name: "Alice",
      email: "alice@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    post login_path, params: { auth: { email: user.email, password: "wrong-password" } }

    assert_response :unprocessable_entity
  end

  test "logs out authenticated user" do
    user = User.create!(
      username: "bob",
      display_name: "Bob",
      email: "bob@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(user)
    assert_redirected_to dashboard_path

    post logout_path

    assert_redirected_to root_path
  end
end
