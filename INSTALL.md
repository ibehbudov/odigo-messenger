# Installing Odigo

Download the installer for your OS from the [Releases page](https://github.com/ibehbudov/odigo-messenger/releases).

| OS | File |
|----|------|
| macOS (Apple Silicon / M1–M4) | `Odigo-<version>-arm64.dmg` |
| macOS (Intel) | `Odigo-<version>-x64.dmg` |
| Windows | `Odigo-<version>.exe` |
| Linux | `Odigo-<version>.AppImage` or `Odigo-<version>.deb` |

## macOS — "Odigo is damaged and can't be opened"

This message does **not** mean the file is broken. Odigo is not signed with a paid
Apple Developer ID, so macOS puts a *quarantine* flag on anything unsigned that you
download, and on Apple Silicon it shows this exact wording.

To open it:

1. Open the `.dmg` and drag **Odigo** into your **Applications** folder.
2. Open **Terminal** and run:
   ```bash
   xattr -cr /Applications/Odigo.app
   ```
   If that doesn't work, try:
   ```bash
   sudo xattr -rd com.apple.quarantine /Applications/Odigo.app
   ```
3. Launch Odigo from Applications — it will open normally from now on.

> Right-clicking → **Open** is *not* enough for the "damaged" message; the `xattr`
> command above is what removes the quarantine flag.

### Removing this step for good (optional)
The only way to make Odigo open with a normal double-click (no Terminal) is to sign
and **notarize** it with an Apple Developer ID (a paid Apple Developer account, $99/yr).
The CI is already wired to notarize when these repository secrets are present:
`NATIVEPHP_APPLE_ID`, `NATIVEPHP_APPLE_ID_PASS` (an app-specific password) and
`NATIVEPHP_APPLE_TEAM_ID`, plus a Developer ID Application certificate.

## Windows

Windows SmartScreen may show "Windows protected your PC" for the unsigned `.exe`.
Click **More info → Run anyway**.

## Linux

- **AppImage:** `chmod +x Odigo-*.AppImage` then run it.
- **.deb:** `sudo dpkg -i Odigo-*.deb` (or open with your software installer).
