require "test_helper"

class HomeControllerTest < ActionDispatch::IntegrationTest
  test "guest landing renders the refreshed home" do
    get root_path

    assert_response :success
    assert_match "<h1>", response.body
    assert_match "Um lugar", response.body
    assert_match "evoluir e", response.body
    assert_match "companhia.", response.body
    assert_match %r{/assets/home/home_mog-[a-f0-9]+\.png}, response.body
    assert_match /<body[^>]*class="[^"]*home-page[^"]*"/, response.body
    assert_match "Criar conta", response.body
    assert_match "Entrar", response.body
    assert_select "button[data-theme-toggle][aria-pressed='false']", count: 1
    assert_select "button[data-theme-toggle] .theme-toggle-moon", count: 1
    assert_select "button[data-theme-toggle] .theme-toggle-sun", count: 1
    assert_operator response.body.index("theme-toggle-moon"), :<, response.body.index("theme-toggle-sun")
  end

  test "authenticated users are redirected away from landing" do
    user = User.create!(
      username: "landinguser",
      display_name: "Landing User",
      email: "landinguser@example.com",
      password: "password123",
      password_confirmation: "password123"
    )

    sign_in_as(user)

    get root_path

    assert_redirected_to dashboard_path
  end
end
