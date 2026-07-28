namespace :import do
  desc "Import data from the Laravel database into Rails"
  task laravel: :environment do
    LaravelImporter.new.run
    puts "Laravel data import completed."
  end
end
