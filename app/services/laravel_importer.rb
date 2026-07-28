class LaravelImporter
  class SourceBase < ActiveRecord::Base
    self.abstract_class = true
  end

  def initialize(database_url: ENV["SOURCE_DATABASE_URL"], storage_root: ENV["SOURCE_STORAGE_ROOT"] || Rails.root.join("..", "storage", "app", "public").to_s)
    @database_url = database_url
    @storage_root = storage_root
  end

  def run
    raise ArgumentError, "SOURCE_DATABASE_URL is required" if @database_url.blank?

    # Import order matters: parents are loaded before dependent rows so we can
    # keep foreign keys and identifiers stable across repeated runs.
    SourceBase.establish_connection(@database_url)

    import_users
    import_study_subjects
    import_study_groups
    import_study_group_members
    import_study_focus_rooms
    import_study_sessions
    import_study_focus_participations
    import_daily_logs
    import_friendships
    import_circle_posts
    import_circle_post_replies
    import_study_rooms
    import_study_room_participants
  ensure
    SourceBase.connection_pool.disconnect! if SourceBase.connected?
  end

  private

  def source_model(table_name)
    Class.new(SourceBase) do
      self.table_name = table_name
    end
  end

  def import_users
    source_model("users").find_each do |row|
      user = User.find_or_initialize_by(id: row.id)
      user.assign_attributes(
        username: row.username,
        display_name: row.display_name,
        profile_title: row.profile_title,
        email: row.email,
        password_digest: row.password,
        bio: row.bio,
        profile_photo_path: row.profile_photo_path,
        readme_markdown: row.readme_markdown,
        last_login_at: row.last_login_at,
        deleted_at: row.deleted_at,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      user.save!(validate: false)
      attach_file(user.profile_photo, row.profile_photo_path)
    end
  end

  def import_study_subjects
    source_model("study_subjects").find_each do |row|
      subject = StudySubject.find_or_initialize_by(id: row.id)
      subject.assign_attributes(
        user_id: row.user_id,
        name: row.name,
        description: row.description,
        goal_period: row.goal_period,
        goal_minutes: row.goal_minutes,
        photo_path: row.photo_path,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      subject.save!(validate: false)
      attach_file(subject.photo, row.photo_path)
    end
  end

  def import_study_groups
    source_model("study_groups").find_each do |row|
      group = StudyGroup.find_or_initialize_by(id: row.id)
      group.assign_attributes(
        owner_id: row.owner_id,
        name: row.name,
        code: row.code,
        description: row.description,
        visibility: row.visibility,
        password_hash: row.password_hash,
        status: row.status,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      group.save!(validate: false)
    end
  end

  def import_study_group_members
    source_model("study_group_members").find_each do |row|
      member = StudyGroupMember.find_or_initialize_by(id: row.id)
      member.assign_attributes(
        study_group_id: row.study_group_id,
        user_id: row.user_id,
        role: row.role,
        joined_at: row.joined_at,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      member.save!(validate: false)
    end
  end

  def import_study_focus_rooms
    source_model("study_focus_rooms").find_each do |row|
      room = StudyFocusRoom.find_or_initialize_by(id: row.id)
      room.assign_attributes(
        study_group_id: row.study_group_id,
        name: row.name,
        description: row.description,
        icon: row.icon,
        position: row.position,
        is_active: row.is_active,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      room.save!(validate: false)
    end
  end

  def import_study_sessions
    source_model("study_sessions").find_each do |row|
      session = StudySession.find_or_initialize_by(id: row.id)
      session.assign_attributes(
        user_id: row.user_id,
        study_subject_id: row.study_subject_id,
        study_group_id: row.study_group_id,
        study_focus_room_id: row.study_focus_room_id,
        subject: row.subject,
        notes: row.notes,
        source_type: row.source_type,
        source_id: row.source_id,
        started_at: row.started_at,
        ended_at: row.ended_at,
        paused_at: row.paused_at,
        paused_seconds: row.paused_seconds,
        duration_seconds: row.duration_seconds,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      session.save!(validate: false)
    end
  end

  def import_study_focus_participations
    source_model("study_focus_participations").find_each do |row|
      participation = StudyFocusParticipation.find_or_initialize_by(id: row.id)
      participation.assign_attributes(
        study_focus_room_id: row.study_focus_room_id,
        study_session_id: row.study_session_id,
        user_id: row.user_id,
        study_subject_id: row.study_subject_id,
        started_at: row.started_at,
        ended_at: row.ended_at,
        paused_at: row.paused_at,
        paused_seconds: row.paused_seconds,
        duration_seconds: row.duration_seconds,
        status: row.status,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      participation.save!(validate: false)
    end
  end

  def import_daily_logs
    source_model("daily_logs").find_each do |row|
      log = DailyLog.find_or_initialize_by(id: row.id)
      log.assign_attributes(
        user_id: row.user_id,
        log_date: row.log_date,
        title: row.title,
        content: row.content,
        study_minutes: row.study_minutes,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      log.save!(validate: false)
    end
  end

  def import_friendships
    source_model("friendships").find_each do |row|
      friendship = Friendship.find_or_initialize_by(id: row.id)
      friendship.assign_attributes(
        requester_id: row.requester_id,
        addressee_id: row.addressee_id,
        status: row.status,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      friendship.save!(validate: false)
    end
  end

  def import_circle_posts
    source_model("circle_posts").find_each do |row|
      post = CirclePost.find_or_initialize_by(id: row.id)
      post.assign_attributes(
        user_id: row.user_id,
        title: row.title,
        body: row.body,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      post.save!(validate: false)
    end
  end

  def import_circle_post_replies
    source_model("circle_post_replies").find_each do |row|
      reply = CirclePostReply.find_or_initialize_by(id: row.id)
      reply.assign_attributes(
        circle_post_id: row.circle_post_id,
        user_id: row.user_id,
        body: row.body,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      reply.save!(validate: false)
    end
  end

  def import_study_rooms
    source_model("study_rooms").find_each do |row|
      room = StudyRoom.find_or_initialize_by(id: row.id)
      room.assign_attributes(
        owner_id: row.owner_id,
        name: row.name,
        subject: row.subject,
        visibility: row.visibility,
        code: row.code,
        started_at: row.started_at,
        ended_at: row.ended_at,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      room.save!(validate: false)
    end
  end

  def import_study_room_participants
    source_model("study_room_participants").find_each do |row|
      participant = StudyRoomParticipant.find_or_initialize_by(id: row.id)
      participant.assign_attributes(
        study_room_id: row.study_room_id,
        user_id: row.user_id,
        joined_at: row.joined_at,
        left_at: row.left_at,
        created_at: row.created_at,
        updated_at: row.updated_at
      )
      participant.save!(validate: false)
    end
  end

  def attach_file(attachment, relative_path)
    return if relative_path.blank? || attachment.attached?

    file_path = File.join(@storage_root, relative_path)
    return unless File.exist?(file_path)

    # Preserve the original file contents from Laravel storage without
    # re-downloading or rewriting already attached blobs.
    attachment.attach(
      io: File.open(file_path),
      filename: File.basename(file_path)
    )
  end
end
