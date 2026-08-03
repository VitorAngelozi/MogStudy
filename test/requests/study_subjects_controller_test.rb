require "test_helper"

class StudySubjectsControllerTest < ActionDispatch::IntegrationTest
  test "creates updates and destroys study subjects" do
    user = User.create!(
      username: "subjectuser",
      display_name: "Subject User",
      email: "subjectuser@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(user)

    assert_difference -> { user.study_subjects.count }, 1 do
      post study_subjects_path, params: {
        study_subject: {
          name: "Physics",
          description: "Mechanics",
          goal_value: "3",
          goal_unit: "hours"
        }
      }
    end

    subject = user.study_subjects.find_by!(name: "Physics")

    patch study_subject_path(subject), params: {
      study_subject: {
        name: "Advanced Physics",
        description: "Mechanics and optics",
        goal_value: "4",
        goal_unit: "hours"
      }
    }

    assert_redirected_to dashboard_path
    assert_equal "Advanced Physics", subject.reload.name
    assert_equal 240, subject.goal_minutes

    assert_difference -> { user.study_subjects.count }, -1 do
      delete study_subject_path(subject)
    end

    assert_redirected_to study_subjects_path
  end
end
