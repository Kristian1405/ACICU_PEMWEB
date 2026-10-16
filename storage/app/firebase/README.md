# Folder Service Account Firebase

Letakkan file **Service Account** (JSON) dari Firebase Console di folder ini:

```
storage/app/firebase/service-account.json
```

Di VPS (Docker), folder ini di-bind-mount ke container sehingga file cukup
ditaruh di sini lalu restart container.

- File `*.json` di folder ini **di-ignore Git** dan **di-ignore Docker**
  (tidak ikut ter-commit maupun masuk ke image).
- Pastikan `FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json`
  ada di file `.env`.
- Di Linux, beri izin baca: `chmod 644 storage/app/firebase/service-account.json`.

Cara mendapatkan file ini: Firebase Console → Project settings →
Service accounts → Generate new private key.
