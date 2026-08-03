require "test_helper"

class DashboardControllerTest < ActionDispatch::IntegrationTest
  include ActiveSupport::Testing::TimeHelpers

  test "dashboard renders with friend search json" do
    travel_to(Time.zone.today + 2.days) do
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

      subject = viewer.study_subjects.create!(name: "Mathematics", goal_minutes: 180)
      today_start = Time.zone.today.beginning_of_day
      yesterday_start = 1.day.ago.beginning_of_day

      viewer.study_sessions.create!(
        subject: subject.name,
        study_subject: subject,
        started_at: today_start + 9.hours,
        ended_at: today_start + 10.hours,
        duration_seconds: 3600
      )
      viewer.study_sessions.create!(
        subject: subject.name,
        study_subject: subject,
        started_at: today_start + 13.hours,
        ended_at: today_start + 13.hours + 30.minutes,
        duration_seconds: 1800
      )
      viewer.study_sessions.create!(
        subject: subject.name,
        study_subject: subject,
        started_at: yesterday_start + 11.hours,
        ended_at: yesterday_start + 11.hours + 45.minutes,
        duration_seconds: 2700
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
      assert_match %r{/assets/branding/mogstudy_cat_main-[a-f0-9]+\.png}, response.body

      metric_cards = metric_card_data(response.body)
      assert_includes metric_cards, [ "Horas estudadas", "2h15", "tempo acumulado" ]
      assert_includes metric_cards, [ "Dias de estudo", "2", "dias com estudo" ]
      assert_includes metric_cards, [ "Dias seguidos", "2", "sequencia atual" ]
      assert_includes metric_cards, [ "Meta semanal", "75%", "ritmo consistente" ]

      get friend_search_path, params: { friend_search: "friend" }

      assert_response :success
      payload = json_response
      assert_equal "friend", payload["query"].downcase
      assert_equal true, payload["has_search"]
      assert_equal "friend", payload["results"].first["username"]
      assert_equal "accepted", payload["results"].first["friendship"]["state"]
    end
  end

  test "dashboard shows empty states when there is no recent activity or achievements" do
    user = User.create!(
      username: "quietuser",
      display_name: "Quiet User",
      email: "quiet@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(user)

    get dashboard_path

    assert_response :success
    assert_match "Nenhuma atividade recente ainda.", response.body
    assert_match "Sem conquistas ainda.", response.body
    refute_match "Maria completou", response.body
    refute_match "Lucas criou", response.body
  end

  test "dashboard shows first study badge after the first completed session" do
    user = User.create!(
      username: "firststudyuser",
      display_name: "First Study User",
      email: "firststudy@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = user.study_subjects.create!(name: "Physics")

    user.study_sessions.create!(
      subject: subject.name,
      study_subject: subject,
      started_at: 2.days.ago,
      ended_at: 2.days.ago + 45.minutes,
      duration_seconds: 2700
    )

    sign_in_as(user)

    get dashboard_path

    assert_response :success
    assert_match %r{/assets/badges/badge_firstStudy-[a-f0-9]+\.png}, response.body
    assert_match "Primeiro estudo registrado", response.body
  end

  test "dashboard does not show first study badge for an open session" do
    user = User.create!(
      username: "openstudyuser",
      display_name: "Open Study User",
      email: "openstudy@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = user.study_subjects.create!(name: "Chemistry")

    user.study_sessions.create!(
      subject: subject.name,
      study_subject: subject,
      started_at: Time.current,
      duration_seconds: 0
    )

    sign_in_as(user)

    get dashboard_path

    assert_response :success
    refute_match %r{/assets/badges/badge_firstStudy-[a-f0-9]+\.png}, response.body
  end

  test "dashboard counts study days uniquely and breaks streak on gaps" do
    user = User.create!(
      username: "streakuser",
      display_name: "Streak User",
      email: "streak@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = user.study_subjects.create!(name: "History", goal_minutes: 120)

    [ 0, 1, 3 ].each do |days_ago|
      day_start = days_ago.days.ago.beginning_of_day

      user.study_sessions.create!(
        subject: subject.name,
        study_subject: subject,
        started_at: day_start + 8.hours,
        ended_at: day_start + 9.hours,
        duration_seconds: 3600
      )
    end

    sign_in_as(user)

    get dashboard_path

    assert_response :success

    metric_cards = metric_card_data(response.body)
    assert_includes metric_cards, [ "Dias de estudo", "3", "dias com estudo" ]
    assert_includes metric_cards, [ "Dias seguidos", "2", "sequencia atual" ]
  end

  private

  def metric_card_data(body)
    Nokogiri::HTML(body).css(".metric-card").map do |card|
      [
        card.at_css(".metric-copy span")&.text&.strip,
        card.at_css(".metric-copy strong")&.text&.strip,
        card.at_css(".metric-copy small")&.text&.strip
      ]
    end
  end
end
