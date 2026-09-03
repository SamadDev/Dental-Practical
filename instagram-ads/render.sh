#!/bin/bash
# Renders all Instagram ad assets. Edit src/brand.json first, then run: ./render.sh
set -e
cd "$(dirname "$0")"

CHROME='/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
FPS=30; DUR=7; N=$((FPS * DUR))

python3 build.py

shot() { # shot <file-with-query> <WxH> <out.png>
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 \
    --virtual-time-budget=2500 --window-size="$2" --default-background-color=FFFFFFFF \
    --screenshot="$3" "file://$PWD/build/$1" 2>/dev/null
}

echo '--- static posts & stories ---'
shot "post.html?lang=ku"  "1080,1080" out/post-intro-ku.png
shot "post.html?lang=en"  "1080,1080" out/post-intro-en.png
shot "story.html?lang=ku" "1080,1920" out/story-intro-ku.png
shot "story.html?lang=en" "1080,1920" out/story-intro-en.png

echo "--- video: $N frames @ ${FPS}fps (${DUR}s) ---"
rm -rf tmp/frames && mkdir -p tmp/frames
for ((f=0; f<N; f++)); do
  printf -v ff '%04d' "$f"
  shot "story.html?lang=ku&f=$f" "1080,1920" "tmp/frames/f_$ff.png"
done

ffmpeg -y -framerate "$FPS" -i tmp/frames/f_%04d.png \
  -c:v libx264 -pix_fmt yuv420p -crf 18 -movflags +faststart \
  out/story-intro-ku.mp4 2>/tmp/ffmpeg.log

echo 'done:' && ls -la out/