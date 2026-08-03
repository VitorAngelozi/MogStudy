require "test_helper"

class StudyGroupsControllerTest < ActionDispatch::IntegrationTest
  include ActiveSupport::Testing::TimeHelpers

  test "creates joins shows and serves presence for study groups" do
    owner = User.create!(
      username: "groupowner",
      display_name: "Group Owner",
      email: "groupowner@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    member = User.create!(
      username: "groupmember",
      display_name: "Group Member",
      email: "groupmember@example.com",
      password: "password123",
      password_confirmation: "password123"
    )
    subject = member.study_subjects.create!(name: "History", goal_minutes: 90)

    sign_in_as(owner)

    assert_difference -> { StudyGroup.count }, 1 do
      post study_groups_path, params: {
        study_group: {
          name: "Alpha Group",
          description: "Study together",
          visibility: StudyGroup::VISIBILITY_PUBLIC
        }
      }
    end

    group = StudyGroup.find_by!(name: "Alpha Group")
    assert_equal owner, group.owner
    assert_equal 1, group.members.count

    get study_groups_path
    assert_response :success

    sign_in_as(member)

    post join_by_code_study_groups_path, params: { code: group.code }
    assert_equal 2, group.reload.members.count

    group.focus_rooms.create!(name: "Main room", description: "Focus", icon: "book", position: 1, is_active: true)
    room = group.focus_rooms.first

    travel_to Time.zone.local(2026, 7, 27, 10, 0, 0) do
      post start_focus_study_study_group_path(group, focus_room_id: room.id), params: {
        focus_session: {
          study_subject_id: subject.id,
          notes: "History block"
        }
      }

      session_record = member.study_sessions.order(:created_at).last
      assert_not_nil session_record
      assert_equal group, session_record.study_group

      get presence_study_group_path(group)
      payload = json_response
      assert_equal 1, payload["active_count"]
      assert_equal room.name, payload["participants"].first["room"]
    end

    post leave_study_group_path(group)
    assert_equal 1, group.reload.members.count
  end
end
