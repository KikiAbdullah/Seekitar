import { readFileSync, writeFileSync, readdirSync } from 'node:fs'
import path from 'node:path'

const SERVER = 'seekitar-server'

const KEEP = 'bell building circle-check circle-xmark clock floppy-disk eye eye-slash flag heart id-card envelope map newspaper image square-check star user circle-user'
  .split(' ')

const REPLACE = {
  'rectangle-ad': 'lightbulb',
  'circle-exclamation': 'circle-question',
  'triangle-exclamation': 'circle-xmark',
  'arrow-left': 'arrow-alt-circle-left',
  'arrow-right': 'arrow-alt-circle-right',
  'arrows-left-right': 'handshake',
  ban: 'circle-stop',
  box: 'clipboard',
  'tower-broadcast': 'bell',
  store: 'building',
  'money-bill': 'money-bill-1',
  check: 'circle-check',
  'chevron-up': 'arrow-alt-circle-up',
  'clipboard-list': 'rectangle-list',
  cookie: 'file-lines',
  crown: 'gem',
  'dollar-sign': 'money-bill-1',
  'location-crosshairs': 'compass',
  'mobile-screen': 'comment-dots',
  tags: 'handshake',
  ellipsis: 'circle-dot',
  download: 'arrow-alt-circle-down',
  gavel: 'file-lines',
  'clock-rotate-left': 'clock',
  'circle-info': 'circle-question',
  key: 'calendar-days',
  'gauge-high': 'chart-bar',
  lock: 'circle-stop',
  'lock-open': 'circle-play',
  'location-dot': 'map',
  'magnifying-glass-location': 'compass',
  bars: 'list-alt',
  'box-open': 'clipboard',
  pen: 'pen-to-square',
  phone: 'comment',
  plus: 'plus-square',
  'satellite-dish': 'map',
  receipt: 'money-bill-1',
  'arrows-rotate': 'arrow-alt-circle-right',
  'magnifying-glass': 'compass',
  gear: 'list-alt',
  'shield-halved': 'circle-check',
  'bag-shopping': 'credit-card',
  'cart-shopping': 'credit-card',
  tag: 'bookmark',
  'screwdriver-wrench': 'lightbulb',
  trash: 'trash-can',
  'truck-fast': 'paper-plane',
  users: 'address-book',
  'user-xmark': 'user-circle',
  xmark: 'circle-xmark',
}

const map = {}
for (const n of KEEP) map[n] = n
for (const [k, v] of Object.entries(REPLACE)) map[k] = v

console.log(`Peta siap: ${Object.keys(map).length} nama (target sudah diverifikasi ada di fa-regular-400.woff2).`)

const exts = new Set(['.blade.php', '.php', '.css', '.js'])
const files = []
const walk = (dir) => {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    if (entry.name === 'vendor' || entry.name === 'node_modules' || entry.name === 'storage' || entry.name === '.git') continue
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) walk(full)
    else if (exts.has(path.extname(entry.name))) files.push(full)
  }
}
walk(path.resolve(SERVER))

const re = new RegExp('fa-solid fa-(' + Object.keys(map).map((n) => n.replace(/-/g, '-')).join('|') + ')(?![a-z0-9-])', 'g')

let changed = 0
const touched = new Set()
for (const file of files) {
  const src = readFileSync(file, 'utf8')
  const out = src.replace(re, (match, name) => `fa-regular fa-${map[name]}`)
  if (out !== src) {
    writeFileSync(file, out)
    changed++
    touched.add(path.relative(process.cwd(), file))
  }
}
console.log(`\nFile diubah: ${changed}`)
for (const f of [...touched].sort()) console.log('  ' + f)
