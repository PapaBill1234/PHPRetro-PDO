# Polaris-imager integration

PHPRetro can render every avatar through a self-hosted
[Polaris-imager](https://github.com/duckietm/Polaris-imager) service. The
website passes each user's PolarIS `look` figure string to the service; it does
not copy, alter, or write any PolarIS table.

## Website configuration

Set this value in the ignored PHPRetro `.env` only after the imager's health
check is healthy:

```dotenv
AVATAR_IMAGING_URL="https://hotel.example/avatarimage"
```

PHPRetro sends the imager `figure`, `size`, `direction`, `head_direction`,
`action=std`, `gesture`, and `img_format=png` parameters. Existing small
avatars map to the imager's `s` size and existing full-size avatars map to its
normal `n` size.

Leaving the value blank uses `https://www.habbo.com/habbo-imaging/avatarimage`
as a temporary fallback. It is only a rollout fallback; production should set
the self-hosted endpoint.

## Deploy Polaris-imager

Deploy [duckietm/Polaris-imager](https://github.com/duckietm/Polaris-imager)
as a separate Docker service. Do not place its source code or generated assets
inside PHPRetro.

1. Install Docker on the Linux production host. This XAMPP workstation has no
   Docker installation, so it cannot run the production container here.
2. Copy the upstream repository to the service host and create its ignored
   `.env` from the upstream example.
3. Set `NITRO_GAMEDATA_URL` and `NITRO_ASSET_URL` to the same current Nitro
   gamedata and bundled asset locations used by the hotel client. PHPRetro's
   old `xml/figuredata.xml` is not a Nitro gamedata replacement.
4. Start it with `docker compose up -d --build`, then verify
   `http://127.0.0.1:8082/health` from the service host.
5. Put the service behind the hotel HTTPS reverse proxy as `/avatarimage` and
   verify a real figure returns an `image/png` response.
6. Set `AVATAR_IMAGING_URL` in PHPRetro, restart Apache, and test profile,
   minimail, forum, friend-list, search, and wardrobe avatar previews.

Keep the imager private behind the same public site domain or configure its
allowed origins and rate limits before exposing it. Its gamedata/assets must
stay version-aligned with the Nitro client, or new clothing and effects will
not render correctly.

## Rollback

Clear `AVATAR_IMAGING_URL` from PHPRetro's `.env` and restart Apache. The
website immediately returns to the temporary HTTPS fallback without a database
migration or a PolarIS change.
