const enc = new TextEncoder();
const ITER = 15000; // kept modest so login fits the Workers free-plan CPU limit

const toHex = (b: ArrayBuffer | Uint8Array) => [...new Uint8Array(b)].map(x => x.toString(16).padStart(2, '0')).join('');
const fromHex = (h: string) => Uint8Array.from(h.match(/.{2}/g) ?? [], x => parseInt(x, 16));

async function derive(password: string, salt: Uint8Array, iter: number) {
  const key = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, ['deriveBits']);
  return crypto.subtle.deriveBits({ name: 'PBKDF2', hash: 'SHA-256', salt, iterations: iter }, key, 256);
}

/** Format: pbkdf2$iterations$saltHex$hashHex */
export async function hashPassword(password: string): Promise<string> {
  const salt = crypto.getRandomValues(new Uint8Array(16));
  return `pbkdf2$${ITER}$${toHex(salt)}$${toHex(await derive(password, salt, ITER))}`;
}

export async function verifyPassword(password: string, stored: string): Promise<boolean> {
  const [alg, iter, salt, hash] = stored.split('$');
  if (alg !== 'pbkdf2' || !hash) return false;
  const got = toHex(await derive(password, fromHex(salt), Number(iter)));
  if (got.length !== hash.length) return false;
  let diff = 0;
  for (let i = 0; i < got.length; i++) diff |= got.charCodeAt(i) ^ hash.charCodeAt(i);
  return diff === 0;
}
