# WordPress Configurator

This project provides tools and scripts to help automate the initial configuration of a WordPress site, including plugin management and general settings.

## Structure

- `naro-config/`: The main WordPress plugin for configuration.
- `build/`: Build artifacts and packaged plugins.
- `build-pluggin.ps1`: PowerShell script to build and package the plugin.
- `build-includes.txt`: List of additional files/folders to include in the build.

## Usage

1. **Build the Plugin**  
   Run the script to update the version and create a ZIP package:

   ```sh
   sh ./build-pluggin.sh
   ```

   ```pwsh
   pwsh ./build-pluggin.ps1
   ```

2. **Install the Plugin**  
   Upload the generated ZIP from `build/` to your WordPress site.

3. **Configure WordPress**  
   Use the "Naro Config" menu in the WordPress admin to apply settings and manage plugins.

## License

MIT or as specified in individual files.
