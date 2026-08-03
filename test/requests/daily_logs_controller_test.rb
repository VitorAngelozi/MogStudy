require "test_helper"

class DailyLogsControllerTest < ActionDispatch::IntegrationTest
  test "creates or updates a daily log with study minutes" do
    user = User.create!(
      username: "logger",
      display_name: "Logger",
      email: "logger@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = user.study_subjects.create!(name: "Biology", goal_minutes: 120)

    travel_to Time.zone.local(2026, 7, 27, 8, 0, 0) do
      user.study_sessions.create!(
        subject: subject.name,
        study_subject: subject,
        started_at: Time.zone.local(2026, 7, 27, 6, 0, 0),
        ended_at: Time.zone.local(2026, 7, 27, 7, 30, 0),
        duration_seconds: 5400
      )
    end

    sign_in_as(user)

    assert_difference -> { user.daily_logs.count }, 1 do
      post daily_logs_path, params: {
        daily_log: {
          log_date: "2026-07-27",
          title: "Daily note",
          content: "Completed study blocks"
        }
      }
    end

    log = user.daily_logs.find_by!(log_date: Date.new(2026, 7, 27))
    assert_equal 90, log.study_minutes
  end
end
