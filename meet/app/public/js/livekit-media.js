/**
 * Younimatiq Meet — LiveKit SFU media layer
 * Socket.IO still handles lobby / chat / host controls.
 * This module owns camera, mic, screen, and remote A/V via LiveKit.
 */
(function (global) {
  'use strict';

  const LK = global.LivekitClient;
  if (!LK) {
    console.error('LivekitClient UMD missing');
    return;
  }

  const state = {
    room: null,
    url: '',
    connected: false,
    identity: '',
  };

  function $(id) {
    return document.getElementById(id);
  }

  function ensureTile(identity, name, isLocal, isHost) {
    let tile = $('tile-' + identity);
    if (!tile && typeof createTile === 'function') {
      tile = createTile(identity, name || identity, !!isLocal, !!isHost);
      const grid = $('video-grid');
      if (grid) grid.appendChild(tile);
      if (typeof updateGridLayout === 'function') updateGridLayout();
    }
    return tile;
  }

  function attachTrackToTile(identity, track, name, isHost) {
    const isScreen = track.source === LK.Track.Source.ScreenShare
      || track.source === LK.Track.Source.ScreenShareAudio;
    const tileId = isScreen ? identity + '-screen' : identity;
    const tile = ensureTile(tileId, isScreen ? ((name || identity) + "'s Screen") : (name || identity), false, isHost);
    if (!tile) return;

    if (track.kind === 'video') {
      const video = tile.querySelector('video');
      if (video) {
        track.attach(video);
        video.play().catch(() => {});
        if (typeof showVideo === 'function') showVideo(tile);
        if (isScreen && typeof togglePin === 'function') togglePin(tileId);
      }
    } else if (track.kind === 'audio') {
      // LiveKit attaches audible elements; keep a hidden sink on the tile
      let audio = tile.querySelector('audio.lk-audio');
      if (!audio) {
        audio = document.createElement('audio');
        audio.className = 'lk-audio';
        audio.autoplay = true;
        audio.playsInline = true;
        tile.appendChild(audio);
      }
      track.attach(audio);
    }
  }

  function detachTrack(identity, track) {
    const isScreen = track.source === LK.Track.Source.ScreenShare
      || track.source === LK.Track.Source.ScreenShareAudio;
    const tileId = isScreen ? identity + '-screen' : identity;
    track.detach().forEach((el) => {
      try { el.srcObject = null; } catch (_) {}
    });
    if (isScreen) {
      const tile = $('tile-' + tileId);
      if (tile) {
        if (tile.classList.contains('pinned') && typeof layout !== 'undefined') layout = 'grid';
        tile.remove();
        if (typeof updateGridLayout === 'function') updateGridLayout();
      }
    } else if (track.kind === 'video') {
      const tile = $('tile-' + identity);
      if (tile && typeof showNoVideo === 'function') {
        const name = (global.participants && global.participants[identity] && global.participants[identity].name) || identity;
        showNoVideo(tile, name);
      }
    }
  }

  function bindRoomEvents(room) {
    room.on(LK.RoomEvent.TrackSubscribed, (track, _pub, participant) => {
      const meta = (() => { try { return JSON.parse(participant.metadata || '{}'); } catch (_) { return {}; } })();
      attachTrackToTile(participant.identity, track, participant.name || participant.identity, meta.role === 'host');
      if (typeof participants !== 'undefined') {
        participants[participant.identity] = participants[participant.identity] || {
          name: participant.name || participant.identity,
          isHost: meta.role === 'host',
        };
      }
      if (!participant.identity.endsWith('-screen') && typeof addParticipantItem === 'function') {
        addParticipantItem(participant.identity, participant.name || participant.identity, meta.role === 'host');
      }
    });

    room.on(LK.RoomEvent.TrackUnsubscribed, (track, _pub, participant) => {
      detachTrack(participant.identity, track);
    });

    room.on(LK.RoomEvent.ParticipantDisconnected, (participant) => {
      const id = participant.identity;
      const tile = $('tile-' + id);
      if (tile) {
        if (tile.classList.contains('pinned') && typeof layout !== 'undefined') layout = 'grid';
        tile.remove();
      }
      const screenTile = $('tile-' + id + '-screen');
      if (screenTile) screenTile.remove();
      if (typeof updateGridLayout === 'function') updateGridLayout();
      if (typeof removeParticipantItem === 'function') removeParticipantItem(id);
      if (typeof participants !== 'undefined') delete participants[id];
    });

    room.on(LK.RoomEvent.LocalTrackPublished, (pub) => {
      if (pub.track && pub.track.kind === 'video' && pub.source === LK.Track.Source.ScreenShare) {
        const tile = ensureTile(state.identity + '-screen', (global.MY_NAME || 'You') + "'s Screen", true, false);
        const video = tile && tile.querySelector('video');
        if (video) {
          pub.track.attach(video);
          video.style.transform = 'none';
          if (typeof showVideo === 'function') showVideo(tile);
          if (typeof togglePin === 'function') togglePin(state.identity + '-screen');
        }
      }
    });

    room.on(LK.RoomEvent.Disconnected, () => {
      state.connected = false;
    });

    room.on(LK.RoomEvent.MediaDevicesError, (e) => {
      console.warn('LiveKit media device error', e);
      if (typeof showToast === 'function') showToast('Media device error — check camera/mic permissions');
    });
  }

  async function fetchToken({ roomId, identity, name, isHost, hostToken }) {
    const res = await fetch('/api/livekit-token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      cache: 'no-store',
      body: JSON.stringify({
        roomId,
        identity,
        name,
        isHost: !!isHost,
        host_token: hostToken || '',
      }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.error || ('token http ' + res.status));
    }
    return res.json();
  }

  async function connect(opts) {
    if (state.connected && state.room) return state.room;
    const data = await fetchToken(opts);
    state.url = data.url;
    state.identity = opts.identity;

    const room = new LK.Room({
      adaptiveStream: true,
      dynacast: true,
      videoCaptureDefaults: {
        resolution: LK.VideoPresets.h720.resolution,
        frameRate: 30,
      },
      audioCaptureDefaults: {
        autoGainControl: true,
        echoCancellation: true,
        noiseSuppression: true,
      },
      publishDefaults: {
        dtx: true,
        videoEncoding: LK.VideoPresets.h720.encoding,
        screenShareEncoding: LK.ScreenSharePresets.h1080fps15.encoding,
        videoSimulcastLayers: [LK.VideoPresets.h180, LK.VideoPresets.h360],
        stopMicTrackOnMute: false,
      },
    });

    bindRoomEvents(room);
    await room.connect(data.url, data.token, { autoSubscribe: true });
    state.room = room;
    state.connected = true;

    // Publish existing local preview tracks when available (keeps pre-join UX)
    const localStream = global.localStream;
    if (localStream) {
      const audio = localStream.getAudioTracks()[0];
      const video = localStream.getVideoTracks()[0];
      if (audio) {
        await room.localParticipant.publishTrack(audio, {
          source: LK.Track.Source.Microphone,
          audioBitrate: 64000,
        });
        await room.localParticipant.setMicrophoneEnabled(!!global.isMicOn);
      } else {
        await room.localParticipant.setMicrophoneEnabled(!!global.isMicOn);
      }
      if (video) {
        await room.localParticipant.publishTrack(video, {
          source: LK.Track.Source.Camera,
          videoEncoding: { maxBitrate: 1_500_000, maxFramerate: 30 },
          simulcast: true,
        });
        await room.localParticipant.setCameraEnabled(!!global.isCamOn);
      } else if (global.isCamOn) {
        await room.localParticipant.setCameraEnabled(true);
      }
    } else {
      await room.localParticipant.enableCameraAndMicrophone().catch(() => {});
      await room.localParticipant.setMicrophoneEnabled(!!global.isMicOn);
      await room.localParticipant.setCameraEnabled(!!global.isCamOn);
    }

    // Attach already-subscribed remotes (race on join)
    room.remoteParticipants.forEach((p) => {
      p.trackPublications.forEach((pub) => {
        if (pub.track) {
          const meta = (() => { try { return JSON.parse(p.metadata || '{}'); } catch (_) { return {}; } })();
          attachTrackToTile(p.identity, pub.track, p.name || p.identity, meta.role === 'host');
        }
      });
    });

    if (typeof showToast === 'function') showToast('HD media connected');
    return room;
  }

  async function disconnect() {
    if (state.room) {
      try { await state.room.disconnect(true); } catch (_) {}
    }
    state.room = null;
    state.connected = false;
  }

  async function setMicEnabled(on) {
    if (state.room) await state.room.localParticipant.setMicrophoneEnabled(!!on);
  }

  async function setCamEnabled(on) {
    if (state.room) await state.room.localParticipant.setCameraEnabled(!!on);
  }

  async function setScreenShareEnabled(on) {
    if (!state.room) return false;
    await state.room.localParticipant.setScreenShareEnabled(!!on, {
      audio: true,
      resolution: LK.ScreenSharePresets.h1080fps15.resolution,
    });
    return !!on;
  }

  async function replaceCameraTrack(track) {
    if (!state.room || !track) return;
    const pub = state.room.localParticipant.getTrackPublication(LK.Track.Source.Camera);
    if (pub && pub.track) {
      await pub.track.replaceTrack(track);
    } else {
      await state.room.localParticipant.publishTrack(track, {
        source: LK.Track.Source.Camera,
        videoEncoding: { maxBitrate: 1_500_000, maxFramerate: 30 },
        simulcast: true,
      });
    }
  }

  global.YouniLiveKit = {
    get connected() { return state.connected; },
    get room() { return state.room; },
    connect,
    disconnect,
    setMicEnabled,
    setCamEnabled,
    setScreenShareEnabled,
    replaceCameraTrack,
  };
})(window);
