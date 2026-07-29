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
