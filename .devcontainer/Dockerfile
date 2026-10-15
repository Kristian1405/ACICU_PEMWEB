# Docker development workspace
FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/sources.list.d/yarnpkg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends sqlite3 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace
