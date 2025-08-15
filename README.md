# WordPress Configurator

This project provides tools and scripts to help automate the initial configuration of a WordPress site, including plugin management and general settings.

## Structure

- `naro-config/`: The main WordPress plugin for configuration.
- `naro-config/plugins`: List of additional custom plugins to include, for example a pro plugins ZIP.
- `build.sh`: Shell script to build and package the plugin.
- `build.ps1`: PowerShell script to build and package the plugin.
- `release/`: Build artifacts and packaged plugins.

## Usage

1. **Build the Plugin**  
   Run the script to update the version and create a ZIP package:

   ```sh
   sh ./build.sh
   ```

   ```pwsh
   pwsh ./build.ps1
   ```

2. **Install the Plugin**  
   Upload the generated ZIP from `release/` to your WordPress site or directly from the Plugin installer.

3. **Configure WordPress**  
   Use the "Naro Config" menu in the WordPress admin to apply settings and manage plugins.

## License

MIT or as specified in individual files.
