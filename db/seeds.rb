vitor = User.find_or_create_by!(email: "vitor@example.com") do |user|
  user.username = "vitorangelozi"
  user.display_name = "Vitor Angelozi"
  user.profile_title = "dev"
  user.password = "12345678"
  user.bio = "Construindo consistencia em estudos de backend."
  user.last_login_at = Time.current
end

maria = User.find_or_create_by!(email: "maria@example.com") do |user|
  user.username = "mariasilva"
  user.display_name = "Maria Silva"
  user.profile_title = "analista de dados"
  user.password = "12345678"
  user.bio = "Registro de estudos, foco e disciplina."
  user.last_login_at = Time.current
end

joao = User.find_or_create_by!(email: "joao@example.com") do |user|
  user.username = "joaosantos"
  user.display_name = "Joao Santos"
  user.profile_title = "backend learner"
  user.password = "12345678"
  user.bio = "Perfil de exemplo para o MogStudy."
  user.last_login_at = Time.current
end

laravel = vitor.study_subjects.find_or_create_by!(name: "Laravel") do |subject|
  subject.description = "Backend, autenticacao e rotas."
end

arquitetura = vitor.study_subjects.find_or_create_by!(name: "Arquitetura") do |subject|
  subject.description = "Organizacao do projeto MogStudy."
end

vitor.study_sessions.find_or_create_by!(subject: "Laravel", started_at: 1.day.ago.change(hour: 19, min: 0, sec: 0)) do |session|
  session.study_subject = laravel
  session.notes = "Estudo sobre autenticacao e rotas."
  session.ended_at = 1.day.ago.change(hour: 20, min: 15, sec: 0)
  session.duration_seconds = 4500
end

vitor.study_sessions.find_or_create_by!(subject: "Arquitetura", started_at: 1.day.ago.change(hour: 21, min: 0, sec: 0)) do |session|
  session.study_subject = arquitetura
  session.notes = "Organizacao do projeto MogStudy."
  session.ended_at = 1.day.ago.change(hour: 21, min: 45, sec: 0)
  session.duration_seconds = 2700
end

vitor.daily_logs.find_or_create_by!(log_date: 1.day.ago.to_date) do |log|
  log.title = "Estudo de autenticacao"
  log.content = "Hoje eu revisei o fluxo de login e estrutura de sessao no Laravel."
  log.study_minutes = 120
end

vitor.daily_logs.find_or_create_by!(log_date: Time.zone.today) do |log|
  log.title = "Migrando para MogStudy"
  log.content = "Organizei o projeto para receber timer, perfil publico e feed diario."
  log.study_minutes = 0
end

maria.daily_logs.find_or_create_by!(log_date: 2.days.ago.to_date) do |log|
  log.title = "Rotina minima"
  log.content = "Uma sessao curta ainda conta. O importante e manter o ritmo."
  log.study_minutes = 45
end

joao.daily_logs.find_or_create_by!(log_date: 3.days.ago.to_date) do |log|
  log.title = "Planejamento da semana"
  log.content = "Separei os topicos de hoje e defini uma meta para cada um."
  log.study_minutes = 60
end

group = vitor.owned_study_groups.find_or_create_by!(name: "Medicina - UFMS") do |study_group|
  study_group.code = "UFMS2027"
  study_group.description = "Grupo de estudos."
  study_group.visibility = StudyGroup::VISIBILITY_PUBLIC
  study_group.status = StudyGroup::STATUS_ACTIVE
end

group.members.find_or_create_by!(user: vitor) do |member|
  member.role = StudyGroupMember::ROLE_OWNER
  member.joined_at = Time.current
end

room = group.focus_rooms.find_or_create_by!(name: "Matematica") do |focus_room|
  focus_room.position = 1
  focus_room.icon = "book"
  focus_room.description = "Canal coletivo para exatas."
  focus_room.is_active = true
end

group.focus_rooms.find_or_create_by!(name: "Redacao") do |focus_room|
  focus_room.position = 2
  focus_room.icon = "notes"
  focus_room.description = "Sala para treinos de escrita."
  focus_room.is_active = true
end

Friendship.find_or_create_by!(requester: vitor, addressee: maria) do |friendship|
  friendship.status = Friendship::STATUS_ACCEPTED
end

CirclePost.find_or_create_by!(user: maria, title: "Deploy estudado") do |post|
  post.body = "Revisei filas e cache."
end
