FROM php:8.3-cli@sha256:f62df9916bc05ae3c5120b41a115938f22623b0c98c815cbe2c67bc73cec21ef

WORKDIR /app

RUN apt-get update \
 && apt-get install -y --no-install-recommends build-essential libffi-dev make \
 && docker-php-ext-install ffi \
 && rm -rf /var/lib/apt/lists/*

COPY .vendor/.zed/oresoftware/flags-2-env ./.vendor/.zed/oresoftware/flags-2-env
RUN make -C .vendor/.zed/oresoftware/flags-2-env clean && make -C .vendor/.zed/oresoftware/flags-2-env shared

COPY .cli-flags.toml ./
COPY src ./src

ENV FLAGS2ENV_NATIVE_LIB=/app/.vendor/.zed/oresoftware/flags-2-env/build/libflags2env.so

RUN useradd --create-home --shell /bin/sh --uid 10001 fixture
USER fixture

# FFI is disabled by default in the CLI SAPI; it has to be turned on explicitly.
CMD ["php", "-d", "ffi.enable=true", "src/demo.php"]
