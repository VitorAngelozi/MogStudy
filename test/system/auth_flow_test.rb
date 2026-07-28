require "application_system_test_case"

class AuthFlowTest < ApplicationSystemTestCase
  test "user can register, reach dashboard, and log out" do
    visit register_path

    fill_in "Username", with: "systemuser"
    fill_in "Nome de exibição", with: "System User"
    fill_in "E-mail", with: "system@example.com"
    fill_in "Senha", with: "password123"
    fill_in "Confirmar senha", with: "password123"

    click_button "Criar conta"

    assert_current_path dashboard_path
    assert_text "MogStudy"

    click_button "Sair"

    assert_current_path root_path
  end
end
