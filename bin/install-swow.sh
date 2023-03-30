#!/usr/bin/env sh

success(){ echo "✅  $1"; exit 0; }
info(){ echo "ℹ️  $1";}

info "Installing library. "

apk add --no-cache \
bash \
autoconf \
build-base \
openssl-dev

info "Building php-ext swow. "

php ./../vendor/bin/swow-builder --quiet

info "Clearing cache. "

apk del \
bash \
autoconf \
build-base \
openssl-dev

success "Done. "