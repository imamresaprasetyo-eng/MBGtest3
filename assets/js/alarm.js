(function () {
  const rows = Array.from(document.querySelectorAll("tr[data-transaksi-id]"));
  const currentUsername = document.body.dataset.currentUser || "";
  const alarmButton = document.getElementById("enable-alarm");
  const alarmStatus = document.getElementById("alarm-status");
  const alarmPanel = document.getElementById("alarm-panel");
  const alarmHour = 13;
  let audioContext = null;
  const memoryAlarmKeys = new Set();

  if (!alarmButton || !alarmStatus) return;

  function notificationSupported() {
    return window.isSecureContext && "Notification" in window;
  }

  function setStatus(message) {
    alarmStatus.textContent = message;
  }

  function getDateKey(now) {
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }

  function getAlarmKey(dateKey) {
    return `mbg-alarm:${encodeURIComponent(currentUsername)}:${dateKey}`;
  }

  function hasAlarmFired(key) {
    try {
      return window.localStorage.getItem(key) === "1";
    } catch (error) {
      return memoryAlarmKeys.has(key);
    }
  }

  function markAlarmFired(key) {
    try {
      window.localStorage.setItem(key, "1");
    } catch (error) {
      memoryAlarmKeys.add(key);
    }
  }

  function playAlarmSound() {
    if (!audioContext || audioContext.state !== "running") return false;

    const startTime = audioContext.currentTime;
    [880, 660, 880].forEach((frequency, index) => {
      const oscillator = audioContext.createOscillator();
      const volume = audioContext.createGain();
      const toneStart = startTime + index * 0.34;

      oscillator.type = "sine";
      oscillator.frequency.setValueAtTime(frequency, toneStart);
      volume.gain.setValueAtTime(0.0001, toneStart);
      volume.gain.exponentialRampToValueAtTime(0.18, toneStart + 0.025);
      volume.gain.exponentialRampToValueAtTime(0.0001, toneStart + 0.25);
      oscillator.connect(volume);
      volume.connect(audioContext.destination);
      oscillator.start(toneStart);
      oscillator.stop(toneStart + 0.26);
    });

    return true;
  }

  function getOutstandingRows() {
    return rows.filter((row) =>
      row.dataset.username === currentUsername && row.dataset.status === "Dipinjam"
    );
  }

  function checkAlarm() {
    const outstandingRows = getOutstandingRows();
    const now = new Date();
    const dateKey = getDateKey(now);
    const alarmKey = getAlarmKey(dateKey);

    if (outstandingRows.length === 0) {
      alarmPanel?.classList.remove("is-alarm-due");
      setStatus("Alarm harian pukul 13.00. Tidak ada MBG yang masih dipinjam.");
      return;
    }

    if (now.getHours() < alarmHour) {
      setStatus(`${outstandingRows.length} transaksi Anda masih dipinjam. Pengingat berbunyi pukul 13.00.`);
      return;
    }

    outstandingRows.forEach((row) => row.classList.add("is-overdue"));
    alarmPanel?.classList.add("is-alarm-due");
    if (hasAlarmFired(alarmKey)) {
      setStatus(`Pengingat pukul 13.00 sudah dibunyikan. ${outstandingRows.length} transaksi masih menunggu pengembalian.`);
      return;
    }

    const canNotify = notificationSupported() && Notification.permission === "granted";
    const soundPlayed = playAlarmSound();
    let notificationSent = false;

    if (canNotify) {
      try {
        const notification = new Notification("Waktunya mengembalikan MBG", {
          body: `${outstandingRows.length} transaksi MBG Anda masih dipinjam. Silakan kembalikan sekarang.`,
          tag: `mbg-return-${currentUsername}-${dateKey}`,
          requireInteraction: true
        });
        notification.onclick = () => {
          window.focus();
          notification.close();
        };
        notificationSent = true;
      } catch (error) {
        notificationSent = false;
      }
    }

    if (soundPlayed || notificationSent) {
      markAlarmFired(alarmKey);
      setStatus(`Alarm aktif: ${outstandingRows.length} transaksi masih dipinjam. Segera kembalikan MBG.`);
    } else {
      setStatus(`Ada ${outstandingRows.length} transaksi yang belum dikembalikan. Aktifkan alarm dan izinkan notifikasi di perangkat ini.`);
    }
  }

  function updatePermissionStatus() {
    if (!notificationSupported()) {
      setStatus("Alarm berjalan selama dashboard terbuka. Notifikasi sistem membutuhkan HTTPS atau localhost.");
    } else if (Notification.permission === "granted") {
      setStatus("Notifikasi aktif. Biarkan dashboard tetap terbuka agar alarm pukul 13.00 berjalan.");
    } else if (Notification.permission === "denied") {
      setStatus("Izin notifikasi ditolak. Ubah izin situs di pengaturan browser; dashboard harus tetap terbuka.");
    } else {
      setStatus("Tekan tombol untuk mengizinkan notifikasi dan menguji bunyi alarm pukul 13.00.");
    }
  }

  alarmButton.addEventListener("click", async () => {
    if (notificationSupported() && Notification.permission === "default") {
      try {
        await Notification.requestPermission();
      } catch (error) {
        setStatus("Browser tidak dapat meminta izin notifikasi. Periksa pengaturan situs pada browser.");
      }
    }

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      try {
        audioContext = audioContext || new AudioContextClass();
        await audioContext.resume();
        playAlarmSound();
      } catch (error) {
        audioContext = null;
      }
    }

    updatePermissionStatus();
    checkAlarm();
  });

  updatePermissionStatus();
  checkAlarm();
  window.setInterval(checkAlarm, 30_000);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) checkAlarm();
  });
})();
