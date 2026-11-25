const fs = require('fs');
const path = require('path');
function loadEnv() {
  const p = path.join(__dirname, '.env');
  const out = {};
  if (!fs.existsSync(p)) return out;
  for (const line of fs.readFileSync(p, 'utf8').split('\n')) {
    const m = line.match(/^([^#=]+)=(.*)$/);
    if (!m) continue;
    out[m[1].trim()] = m[2].trim().replace(/^["']|["']$/g, '');
  }
  return out;
}
const fileEnv = loadEnv();
module.exports = {
  apps: [{
    name: 'younimatiq-meet',
    script: 'server.js',
    cwd: '/var/www/younimatiqwebsite/meet/app',
    env: {
      PORT: Number(fileEnv.PORT || 8000),
      MEET_PUBLIC_URL: fileEnv.MEET_PUBLIC_URL || 'https://younimatiq.com',
      MEET_API_SECRET: fileEnv.MEET_API_SECRET,
      LARAVEL_CONTACT_ALERT_URL: fileEnv.LARAVEL_CONTACT_ALERT_URL || 'https://younimatiq.com/webhooks/meet/contact-alert',
      PLATFORM_RECORDING_ENABLED: fileEnv.PLATFORM_RECORDING_ENABLED || 'false',
      MYSQL_HOST: fileEnv.MYSQL_HOST || 'localhost',
      MYSQL_USER: fileEnv.MYSQL_USER || 'vjmeet_user',
      MYSQL_PASSWORD: fileEnv.MYSQL_PASSWORD || '',
      MYSQL_DATABASE: fileEnv.MYSQL_DATABASE || 'vjmeet',
      TURN_HOST: fileEnv.TURN_HOST || 'younimatiq.com',
      TURN_SECRET: fileEnv.TURN_SECRET || '',
      TURN_TTL_SEC: fileEnv.TURN_TTL_SEC || '21600',
      MEET_MAX_PARTICIPANTS_PER_ROOM: fileEnv.MEET_MAX_PARTICIPANTS_PER_ROOM || '20',
      LIVEKIT_ENABLED: fileEnv.LIVEKIT_ENABLED || 'true',
      LIVEKIT_API_KEY: fileEnv.LIVEKIT_API_KEY || 'younimatiq',
      LIVEKIT_API_SECRET: fileEnv.LIVEKIT_API_SECRET || '',
      LIVEKIT_WS_URL: fileEnv.LIVEKIT_WS_URL || 'wss://younimatiq.com',
      LIVEKIT_HTTP_URL: fileEnv.LIVEKIT_HTTP_URL || 'http://127.0.0.1:7880',
    }
  }]
};
