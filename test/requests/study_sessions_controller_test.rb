require "test_helper"

class StudySessionsControllerTest < ActionDispatch::IntegrationTest
  include ActiveSupport::Testing::TimeHelpers

  test "creates pauses resumes and stops a session" do
    user = User.create!(
      username: "sessionuser",
      display_name: "Session User",
      email: "sessionuser@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = user.study_subjects.create!(name: "Chemistry", goal_minutes: 180)

    sign_in_as(user)

    travel_to Time.zone.local(2026, 7, 27, 9, 0, 0) do
      post study_sessions_path, params: {
        study_session: {
          study_subject_id: subject.id,
          study_subject_name: subject.name,
          notes: "Morning focus"
        }
      }

      session_record = user.study_sessions.order(:created_at).last
      assert_not_nil session_record
      assert_nil session_record.ended_at

      post pause_study_session_path(session_record)
      assert_nil session_record.reload.ended_at
      assert_not_nil session_record.paused_at

      travel 2.minutes

      post resume_study_session_path(session_record)
      assert_nil session_record.reload.paused_at
      assert_operator session_record.paused_seconds, :>=, 120

      post stop_study_session_path(session_record)
      assert_not_nil session_record.reload.ended_at
      assert_operator session_record.duration_seconds, :>, 0
    end
  end
end
