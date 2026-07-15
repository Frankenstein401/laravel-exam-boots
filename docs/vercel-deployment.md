# 🚀 Deploying Documentation Web to Vercel

Situs dokumentasi interaktif yang berada di folder `docs/` dapat dideploy ke Vercel secara gratis dan instan menggunakan metode di bawah ini.

---

## Opsi 1: Menggunakan Vercel CLI (Paling Cepat)

Jika kamu memiliki Vercel CLI terpasang di komputer kamu:

1. Buka terminal dan masuk ke folder project.
2. Jalankan perintah deploy dengan mengarahkan root path ke folder `docs`:
   ```bash
   vercel docs/
   ```
3. Ikuti petunjuk di terminal (pilih yes, hubungkan ke akun kamu, dll.).
4. Setelah selesai, kamu akan mendapatkan URL publik (misal: `https://laravel-exam-boots-docs.vercel.app`).

---

## Opsi 2: Menggunakan GitHub Integration (Rekomendasi untuk Tim)

Jika repository kamu sudah di-push ke GitHub/GitLab:

1. Masuk ke [Vercel Dashboard](https://vercel.com/dashboard).
2. Klik tombol **Add New** -> **Project**.
3. Hubungkan ke repository Git kamu dan pilih repository `laravel-exam-boots`.
4. Pada bagian **Configure Project**:
   - Cari opsi **Root Directory** dan klik **Edit**.
   - Arahkan Root Directory ke folder `docs`.
5. Klik **Deploy**! Vercel akan otomatis men-deploy ulang setiap kali kamu melakukan `git push`.

---

## Opsi 3: Deploy Manual via Vercel Dashboard (Drag and Drop)

1. Masuk ke Vercel Dashboard.
2. Buka halaman pembuatan project baru, lalu cari opsi **Drag and Drop** folder.
3. Seret folder `docs` dari komputer kamu ke area drop zona di browser.
4. Klik **Deploy**. Selesai!
