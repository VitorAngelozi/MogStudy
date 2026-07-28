require "test_helper"

class DashboardControllerTest < ActionDispatch::IntegrationTest
  test "dashboard renders with friend search json" do
    viewer = User.create!(
      username: "viewer",
      display_name: "Viewer",
      email: "viewer@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    friend = User.create!(
      username: "friend",
      display_name: "Friend Person",
      email: "friend@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    viewer.study_subjects.create!(name: "Mathematics", goal_minutes: 120)
    viewer.study_sessions.create!(
      subject: "Mathematics",
      study_subject: viewer.study_subjects.first,
      started_at: 2.days.ago,
      ended_at: 2.days.ago + 1.hour,
      duration_seconds: 3600
    )
    viewer.daily_logs.create!(
      log_date: Time.zone.today,
      title: "Daily log",
      content: "Kept studying",
      study_minutes: 60
    )
    CirclePost.create!(user: friend, title: "Study cycle", body: "Lets keep going")
    Friendship.create!(requester: viewer, addressee: friend, status: Friendship::STATUS_ACCEPTED)

    sign_in_as(viewer)

    get dashboard_path

    assert_response :success
    assert_match "Viewer", response.body
    assert_match "Mathematics", response.body

    get friend_search_path, params: { friend_search: "friend" }

    assert_response :success
    payload = json_response
    assert_equal "friend", payload["query"].downcase
    assert_equal true, payload["has_search"]
    assert_equal "friend", payload["results"].first["username"]
    assert_equal "accepted", payload["results"].first["friendship"]["state"]
  end
end
