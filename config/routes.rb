Rails.application.routes.draw do
  get "up" => "rails/health#show", as: :rails_health_check

  root "home#landing"
  get "/home", to: "home#home"

  get "/login", to: "auth#login", as: :login
  post "/login", to: "auth#login_attempt"
  get "/register", to: "auth#register", as: :register
  post "/register", to: "auth#register_store"
  post "/logout", to: "auth#logout", as: :logout

  get "/dashboard", to: "dashboard#index", as: :dashboard
  get "/friend-search", to: "dashboard#friend_search", as: :friend_search

  resources :study_subjects, path: "study-subjects", only: [:index, :create, :update, :destroy]

  resources :study_sessions, path: "study-sessions", only: [:create] do
    member do
      post :pause
      post :resume
      post :stop
    end
  end

  resources :daily_logs, path: "daily-logs", only: [:create]

  post "/friendships/:user_id", to: "friendships#create", as: :friendships
  post "/friendships/:id/accept", to: "friendships#accept", as: :accept_friendship
  delete "/friendships/:id", to: "friendships#destroy", as: :friendship

  post "/circle-posts", to: "circle_posts#create", as: :circle_posts
  post "/circle-posts/:circle_post_id/replies", to: "circle_posts#reply", as: :circle_post_replies

  resources :study_groups, param: :code, path: "study-groups", only: [:index, :show, :create, :update] do
    collection do
      post :join_by_code
    end

    member do
      post :join
      post :leave
      get :presence
      post "focus-rooms", action: :store_focus_room, as: :focus_rooms
      get "focus-rooms/:focus_room_id", action: :show_focus_room, as: :focus_room
      patch "focus-rooms/:focus_room_id", action: :update_focus_room, as: :update_focus_room
      delete "focus-rooms/:focus_room_id", action: :destroy_focus_room, as: :destroy_focus_room
      post "focus-rooms/:focus_room_id/start", action: :start_focus_study, as: :start_focus_study
      post "focus-rooms/:focus_room_id/stop", action: :stop_focus_study, as: :stop_focus_study
    end
  end

  get "/study-groups/create", to: "study_groups#new", as: :new_study_group

  get "/study-rooms", to: redirect("/study-groups")
  post "/study-rooms", to: redirect("/study-groups")
  get "/study-rooms/:code", to: redirect("/study-groups/%{code}")
  post "/study-rooms/:code/join", to: redirect("/study-groups/%{code}/join")
  post "/study-rooms/:code/leave", to: redirect("/study-groups/%{code}/leave")
  post "/study-rooms/:code/close", to: redirect("/study-groups/%{code}")

  get "/u/:username", to: "profiles#show", as: :profile
  put "/profile", to: "profiles#update", as: :update_profile
end
