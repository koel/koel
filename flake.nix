{
  description = "Koel - A personal music streaming server that works";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs { inherit system; };

        # PHP with required extensions
        php = pkgs.php83.buildEnv {
          extensions = ({ enabled, all }: enabled ++ (with all; [
            exif
            gd
            fileinfo
            simplexml
            pdo
            pdo_mysql
            pdo_pgsql
            redis
            zip
            mbstring
            curl
            openssl
            tokenizer
            xml
            ctype
            bcmath
          ]));
          extraConfig = ''
            memory_limit = 512M
            upload_max_filesize = 100M
            post_max_size = 100M
          '';
        };

        # Composer with PHP
        composer = pkgs.phpPackages.composer.override {
          php = php;
        };

        # Node.js and pnpm
        nodejs = pkgs.nodejs_22;
        pnpm = pkgs.pnpm;

        # Development shell dependencies
        devDependencies = with pkgs; [
          php
          composer
          nodejs
          pnpm

          # Redis for cache/queue
          redis

          # FFmpeg for audio processing
          ffmpeg

          # Git for version control
          git

          # Utilities
          which
          curl
          wget
          gnused
          findutils
          procps
          sqlite
        ];

        # Helper scripts
        startScript = pkgs.writeShellScriptBin "koel-start" ''
          set -e

          echo "🎵 Starting Koel Development Environment"
          echo "========================================"

          # Check if .env exists
          if [ ! -f .env ]; then
            echo "📝 Creating .env file from .env.example..."
            cp .env.example .env
            echo "⚠️  Please edit .env and set your configuration"
            echo "   Required: APP_KEY, DB_*, MEDIA_PATH"
            exit 1
          fi

          # Check if vendor directory exists
          if [ ! -d vendor ]; then
            echo "📦 Installing PHP dependencies..."
            composer install
          fi

          # Check if node_modules exists
          if [ ! -d node_modules ]; then
            echo "📦 Installing Node dependencies..."
            pnpm install
          fi

          # Check if APP_KEY is set
          if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
            echo "🔑 Generating application key..."
            php artisan key:generate
          fi

          # Build frontend assets if needed
          if [ ! -d public/build ]; then
            echo "🏗️  Building frontend assets..."
            pnpm run build
          fi

          echo ""
          echo "✅ Environment ready!"
          echo ""
          echo "Starting services..."
          echo "- Web server: http://localhost:8000"
          echo "- Queue worker"
          echo "- Vite dev server"
          echo ""
          echo "Press Ctrl+C to stop all services"
          echo ""

          # Start all services
          composer run dev
        '';

        setupScript = pkgs.writeShellScriptBin "koel-setup" ''
          set -e

          echo "🎵 Koel Initial Setup (SQLite)"
          echo "=============================="

          # Create .env if not exists
          if [ ! -f .env ]; then
            cp .env.example .env

            # Configure for SQLite
            sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite-persistent/' .env
            sed -i 's/^DB_HOST=/#DB_HOST=/' .env
            sed -i 's/^DB_PORT=/#DB_PORT=/' .env
            sed -i 's/^DB_DATABASE=/#DB_DATABASE=/' .env
            sed -i 's/^DB_USERNAME=/#DB_USERNAME=/' .env
            sed -i 's/^DB_PASSWORD=/#DB_PASSWORD=/' .env

            echo "✓ Created .env file (configured for SQLite)"
          fi

          # Create database directory and file
          mkdir -p database
          if [ ! -f database/database.sqlite ]; then
            touch database/database.sqlite
            echo "✓ Created SQLite database file"
          fi

          # Install dependencies
          echo ""
          echo "📦 Installing PHP dependencies..."
          composer install

          echo ""
          echo "📦 Installing Node dependencies..."
          pnpm install

          # Generate key
          echo ""
          echo "🔑 Generating application key..."
          php artisan key:generate

          # Build assets
          echo ""
          echo "🏗️  Building frontend assets..."
          pnpm run build

          echo ""
          echo "✅ Setup complete!"
          echo ""
          echo "Next steps:"
          echo "1. Edit .env and set MEDIA_PATH to your music directory"
          echo "2. Run: koel-init    (to initialize database)"
          echo "3. Run: koel-start   (to start the server)"
        '';

        initScript = pkgs.writeShellScriptBin "koel-init" ''
          set -e

          echo "🎵 Initializing Koel Database"
          echo "============================="

          php artisan koel:init

          echo ""
          echo "✅ Database initialized!"
          echo ""
          echo "Run 'koel-start' to start the server"
        '';

        stopScript = pkgs.writeShellScriptBin "koel-stop" ''
          echo "🛑 Stopping Koel services..."
          pkill -f "php artisan serve" || true
          pkill -f "php artisan queue" || true
          pkill -f "vite" || true
          echo "✅ All services stopped"
        '';

        # Build the application
        koelApp = pkgs.stdenv.mkDerivation {
          pname = "koel";
          version = builtins.readFile ./.version;

          src = ./.;

          nativeBuildInputs = [
            php
            composer
            nodejs
            pnpm
            pkgs.makeWrapper
          ];

          buildPhase = ''
            runHook preBuild

            # Set HOME for composer and npm
            export HOME=$TMPDIR
            export COMPOSER_CACHE_DIR=$TMPDIR/composer-cache
            export npm_config_cache=$TMPDIR/npm-cache

            # Install PHP dependencies
            echo "Installing PHP dependencies..."
            composer install --no-dev --no-interaction --no-progress --optimize-autoloader

            # Install Node dependencies
            echo "Installing Node dependencies..."
            pnpm install --frozen-lockfile

            # Build frontend assets
            echo "Building frontend assets..."
            pnpm run build

            runHook postBuild
          '';

          installPhase = ''
            runHook preInstall

            mkdir -p $out/share/koel

            # Copy application files
            cp -r . $out/share/koel/

            # Remove unnecessary files
            rm -rf $out/share/koel/node_modules
            rm -rf $out/share/koel/tests
            rm -rf $out/share/koel/.git
            rm -rf $out/share/koel/.github

            # Create bin directory and wrapper script
            mkdir -p $out/bin
            makeWrapper ${php}/bin/php $out/bin/koel-artisan \
              --add-flags "$out/share/koel/artisan" \
              --set APP_BASE_PATH "$out/share/koel"

            runHook postInstall
          '';

          meta = with pkgs.lib; {
            description = "A personal music streaming server that works";
            homepage = "https://koel.dev";
            license = licenses.mit;
            maintainers = [];
            platforms = platforms.linux ++ platforms.darwin;
          };
        };

      in
      {
        # Development shell
        devShells.default = pkgs.mkShell {
          buildInputs = devDependencies ++ [
            startScript
            setupScript
            initScript
            stopScript
          ];

          shellHook = ''
            echo "🎵 Koel Development Environment"
            echo "=============================="
            echo "PHP version: $(php --version | head -n 1)"
            echo "Node.js version: $(node --version)"
            echo "Composer version: $(composer --version)"
            echo "pnpm version: $(pnpm --version)"
            echo ""
            echo "🚀 Quick Start Commands:"
            echo "  koel-setup      - First time setup (install deps, build assets)"
            echo "  koel-init       - Initialize database"
            echo "  koel-start      - Start all services (web + queue + vite)"
            echo "  koel-stop       - Stop all services"
            echo ""
            echo "📚 Manual Commands:"
            echo "  composer install        - Install PHP dependencies"
            echo "  pnpm install           - Install Node dependencies"
            echo "  pnpm run build         - Build frontend assets"
            echo "  php artisan koel:init  - Initialize Koel"
            echo "  php artisan test       - Run tests"
            echo "  sqlite3 database/database.sqlite - Access SQLite database"
            echo ""
            echo "💾 Optional Services:"
            echo "  redis-server          - Redis cache (optional, for better performance)"
            echo ""

            # Set up environment
            export PATH="$PWD/vendor/bin:$PATH"

            # Check if this is first run
            if [ ! -f .env ] && [ ! -d vendor ]; then
              echo "👉 First time here? Run: koel-setup"
              echo ""
            fi
          '';
        };

        # Production package
        packages = {
          default = koelApp;
          koel = koelApp;
        };

        # NixOS module for Koel
        nixosModules.default = { config, lib, pkgs, ... }:
          with lib;
          let
            cfg = config.services.koel;
          in
          {
            options.services.koel = {
              enable = mkEnableOption "Koel music streaming server";

              package = mkOption {
                type = types.package;
                default = koelApp;
                description = "The Koel package to use";
              };

              dataDir = mkOption {
                type = types.path;
                default = "/var/lib/koel";
                description = "Directory where Koel stores its data";
              };

              host = mkOption {
                type = types.str;
                default = "127.0.0.1";
                description = "Host to bind to";
              };

              port = mkOption {
                type = types.port;
                default = 8000;
                description = "Port to listen on";
              };

              user = mkOption {
                type = types.str;
                default = "koel";
                description = "User account under which Koel runs";
              };

              group = mkOption {
                type = types.str;
                default = "koel";
                description = "Group under which Koel runs";
              };

              envFile = mkOption {
                type = types.nullOr types.path;
                default = null;
                description = "Path to the .env file";
              };
            };

            config = mkIf cfg.enable {
              users.users.${cfg.user} = {
                isSystemUser = true;
                group = cfg.group;
                home = cfg.dataDir;
                createHome = true;
              };

              users.groups.${cfg.group} = {};

              systemd.services.koel = {
                description = "Koel Music Streaming Server";
                after = [ "network.target" "mysql.service" "redis.service" ];
                wantedBy = [ "multi-user.target" ];

                serviceConfig = {
                  Type = "simple";
                  User = cfg.user;
                  Group = cfg.group;
                  WorkingDirectory = "${cfg.package}/share/koel";
                  EnvironmentFile = mkIf (cfg.envFile != null) cfg.envFile;
                  ExecStart = "${php}/bin/php ${cfg.package}/share/koel/artisan serve --host=${cfg.host} --port=${toString cfg.port}";
                  Restart = "on-failure";
                  RestartSec = 5;

                  # Security hardening
                  NoNewPrivileges = true;
                  PrivateTmp = true;
                  ProtectSystem = "strict";
                  ProtectHome = true;
                  ReadWritePaths = [ cfg.dataDir ];
                };
              };

              systemd.services.koel-queue = {
                description = "Koel Queue Worker";
                after = [ "network.target" "mysql.service" "redis.service" ];
                wantedBy = [ "multi-user.target" ];

                serviceConfig = {
                  Type = "simple";
                  User = cfg.user;
                  Group = cfg.group;
                  WorkingDirectory = "${cfg.package}/share/koel";
                  EnvironmentFile = mkIf (cfg.envFile != null) cfg.envFile;
                  ExecStart = "${php}/bin/php ${cfg.package}/share/koel/artisan queue:work --tries=3";
                  Restart = "on-failure";
                  RestartSec = 5;
                };
              };
            };
          };
      }
    );
}
