# Require any additional compass plugins here.

# Set this to the root of your project when deployed:
http_path = "./"
css_dir = "./"
sass_dir = "scss"
images_dir = "./"

#environment = :development
environment = :production

if environment == :development
  # You can select your preferred output style here (can be overridden via the command line):
  # output_style = :expanded or :nested or :compact or :compressed
  output_style = :nested

  # To enable relative paths to assets via compass helper functions. Uncomment:
  # relative_assets = true

  # To disable debugging comments that display the original location of your selectors. Uncomment:
  line_comments = true

  sass_options = {:debug_info => false}
end

if environment == :production
  # You can select your preferred output style here (can be overridden via the command line):
  # output_style = :expanded or :nested or :compact or :compressed
  output_style = :compact

  # To enable relative paths to assets via compass helper functions. Uncomment:
  # relative_assets = true

  # To disable debugging comments that display the original location of your selectors. Uncomment:
  line_comments = false

  sass_options = {:debug_info => false}
end

Sass::Script::Number.precision = 7
