#!/usr/bin/env python3
"""
Dental Clinic Instructional Video Generator
Uses PIL for frame generation and FFmpeg for video compilation
"""

import os
import sys
import subprocess
from PIL import Image, ImageDraw, ImageFont
import tempfile
import math
import textwrap

# Colors
PRIMARY = (231, 63, 30)      # #E73F1E - Red-orange
BLUE = (59, 130, 246)       # #3b82f6
GREEN = (16, 185, 129)       # #10b981
YELLOW = (245, 158, 11)      # #f59e0b
PURPLE = (139, 92, 246)      # #8b5cf6
DARK = (24, 24, 27)          # #18181b
LIGHT = (250, 250, 250)      # #fafafa
GRAY = (113, 113, 122)       # #71717a
WHITE = (255, 255, 255)
BLACK = (0, 0, 0)

# Video settings
WIDTH = 1920
HEIGHT = 1080
FPS = 30
TEMP_DIR = tempfile.mkdtemp()

def get_font(size, bold=False):
    """Get a system font"""
    font_paths = [
        "/System/Library/Fonts/Helvetica.ttc",
        "/System/Library/Fonts/Arial.ttf",
        "/Library/Fonts/Arial.ttf",
        "/System/Library/Fonts/SFProDisplay-Bold.otf",
    ]
    for path in font_paths:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size)
            except:
                continue
    return ImageFont.load_default()

def draw_gradient_bg(draw, width, height, color1, color2, direction='vertical'):
    """Draw gradient background"""
    for i in range(height):
        ratio = i / height
        r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
        g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
        b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
        draw.line([(0, i), (width, i)], fill=(r, g, b))

def draw_rounded_rect(draw, xy, radius, fill, outline=None):
    """Draw rounded rectangle"""
    x1, y1, x2, y2 = xy
    draw.rounded_rectangle([x1, y1, x2, y2], radius=radius, fill=fill, outline=outline)

def create_frame(text_lines, subtext=None, bg_color=DARK, accent_color=PRIMARY, 
                 icon=None, progress=None, duration=3):
    """Create a single frame with text"""
    img = Image.new('RGB', (WIDTH, HEIGHT), bg_color)
    draw = ImageDraw.Draw(img)
    
    # Add subtle gradient
    for i in range(HEIGHT):
        ratio = i / HEIGHT
        r = int(bg_color[0] * (1 - ratio * 0.1) + bg_color[0] * ratio * 0.1)
        g = int(bg_color[1] * (1 - ratio * 0.1) + bg_color[1] * ratio * 0.1)
        b = int(bg_color[2] * (1 - ratio * 0.1) + bg_color[2] * ratio * 0.1)
        draw.line([(0, i), (WIDTH, i)], fill=(r, g, b))
    
    # Draw accent glow
    if accent_color:
        glow_size = 200
        for i in range(glow_size, 0, -2):
            alpha = int(15 * (1 - i/glow_size))
            r, g, b = accent_color
            draw.ellipse([(WIDTH//2 - i, HEIGHT//2 - i//2), 
                          (WIDTH//2 + i, HEIGHT//2 + i//2)], 
                         fill=(r, g, b, alpha))
    
    # Draw icon if present
    if icon:
        icon_size = 120
        draw_rounded_rect(draw, 
                         [WIDTH//2 - icon_size//2, HEIGHT//3 - icon_size//2,
                          WIDTH//2 + icon_size//2, HEIGHT//3 + icon_size//2],
                         30, accent_color)
    
    # Draw main text
    font_large = get_font(72, bold=True)
    font_medium = get_font(48)
    font_small = get_font(32)
    
    y_pos = HEIGHT//2 if not icon else HEIGHT//2 + 20
    
    for i, line in enumerate(text_lines):
        bbox = draw.textbbox((0, 0), line, font=font_large)
        text_width = bbox[2] - bbox[0]
        x = (WIDTH - text_width) // 2
        draw.text((x, y_pos + i * 80), line, font=font_large, fill=WHITE)
    
    # Draw subtext
    if subtext:
        bbox = draw.textbbox((0, 0), subtext, font=font_medium)
        text_width = bbox[2] - bbox[0]
        x = (WIDTH - text_width) // 2
        draw.text((x, y_pos + len(text_lines) * 80 + 40), subtext, 
                  font=font_medium, fill=GRAY)
    
    # Draw progress bar if needed
    if progress is not None:
        bar_width = 400
        bar_height = 8
        bar_x = (WIDTH - bar_width) // 2
        bar_y = HEIGHT - 100
        
        # Background
        draw.rounded_rectangle([bar_x, bar_y, bar_x + bar_width, bar_y + bar_height],
                               4, fill=(50, 50, 50))
        # Progress
        draw.rounded_rectangle([bar_x, bar_y, bar_x + int(bar_width * progress), bar_y + bar_height],
                               4, fill=accent_color)
    
    return img

def create_scene_frame(scene_type, text, subtext=None, step_num=None, 
                      total_steps=None, color=PRIMARY):
    """Create a scene frame with various layouts"""
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # Background pattern
    for i in range(0, WIDTH, 50):
        for j in range(0, HEIGHT, 50):
            draw.rectangle([i, j, i+1, j+1], fill=(35, 35, 35))
    
    # Header bar
    draw.rectangle([0, 0, WIDTH, 8], fill=color)
    
    # Step indicator
    if step_num and total_steps:
        step_text = f"Step {step_num} of {total_steps}"
        font_small = get_font(24)
        draw.text((50, 40), step_text, font=font_small, fill=GRAY)
    
    # Main content area - card style
    card_padding = 100
    card_height = 500
    draw_rounded_rect(draw,
                     [card_padding, 150, WIDTH - card_padding, 150 + card_height],
                     30, (35, 35, 35))
    
    # Icon area
    icon_size = 100
    icon_x = WIDTH//2 - icon_size//2
    icon_y = 220
    draw_rounded_rect(draw, [icon_x, icon_y, icon_x + icon_size, icon_y + icon_size],
                     25, color)
    
    # Text
    font_title = get_font(56, bold=True)
    font_body = get_font(36)
    
    # Wrap text if needed
    lines = textwrap.wrap(text, width=40)
    y_text = icon_y + icon_size + 50
    for line in lines:
        bbox = draw.textbbox((0, 0), line, font=font_title)
        text_width = bbox[2] - bbox[0]
        x = (WIDTH - text_width) // 2
        draw.text((x, y_text), line, font=font_title, fill=WHITE)
        y_text += 70
    
    # Subtext
    if subtext:
        y_sub = y_text + 20
        sub_lines = textwrap.wrap(subtext, width=50)
        for line in sub_lines:
            bbox = draw.textbbox((0, 0), line, font=font_body)
            text_width = bbox[2] - bbox[0]
            x = (WIDTH - text_width) // 2
            draw.text((x, y_sub), line, font=font_body, fill=GRAY)
            y_sub += 50
    
    # Footer with progress
    if total_steps:
        bar_width = 600
        bar_height = 6
        bar_x = (WIDTH - bar_width) // 2
        bar_y = HEIGHT - 80
        
        draw.rounded_rectangle([bar_x, bar_y, bar_x + bar_width, bar_y + bar_height],
                               3, (50, 50, 50))
        if step_num:
            progress = step_num / total_steps
            draw.rounded_rectangle([bar_x, bar_y, bar_x + int(bar_width * progress), bar_y + bar_height],
                                   3, color)
    
    return img

def create_phone_mockup(draw, x, y, width, height, content_func):
    """Create a phone mockup frame"""
    # Phone body
    phone_color = (40, 40, 40)
    draw_rounded_rect(draw, [x, y, x + width, y + height], 30, phone_color)
    draw_rounded_rect(draw, [x + 8, y + 8, x + width - 8, y + height - 8], 25, (25, 25, 25))
    
    # Screen area
    screen_pad = 20
    screen_x1 = x + screen_pad
    screen_y1 = y + 50
    screen_x2 = x + width - screen_pad
    screen_y2 = y + height - 40
    draw.rectangle([screen_x1, screen_y1, screen_x2, screen_y2], fill=(20, 20, 20))
    
    # Top bar
    draw.rectangle([screen_x1, screen_y1, screen_x2, screen_y1 + 30], fill=(30, 30, 30))
    
    # Content
    if content_func:
        content_func(draw, screen_x1, screen_y1, screen_x2 - screen_x1, screen_y2 - screen_y1)

def create_app_frame(title, elements, step_num=None, total_steps=None, color=PRIMARY):
    """Create a frame showing app UI"""
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # Header
    draw.rectangle([0, 0, WIDTH, 100], fill=(30, 30, 30))
    font_title = get_font(36, bold=True)
    draw.text((50, 30), title, font=font_title, fill=WHITE)
    
    # Phone mockups side by side
    phone_width = 350
    phone_height = 600
    phone_y = 150
    gap = 80
    
    # Left phone - Patient view
    left_x = WIDTH//2 - phone_width - gap//2
    right_x = WIDTH//2 + gap//2
    
    def left_phone_content(d, sx, sy, sw, sh):
        # Queue list mockup
        font_small = get_font(20)
        d.text((sx + 20, sy + 50), "Today's Queue", font=font_small, fill=WHITE)
        
        # Patient items
        y = sy + 90
        for i, name in enumerate(["Ahmed Kareem", "Sara Hassan", "Ali Mahmud"]):
            status_colors = [(59, 130, 246), (16, 185, 129), (245, 158, 11)]
            # Card
            d.rounded_rectangle([sx + 15, y, sx + sw - 15, y + 70], 10, (35, 35, 35))
            # Avatar
            d.ellipse([sx + 30, y + 15, sx + 60, y + 45], fill=status_colors[i % 3])
            # Name
            d.text((sx + 75, y + 20), name, font=font_small, fill=WHITE)
            # Status
            status = ["In Treatment", "Waiting", "Next"]
            d.text((sx + 75, y + 40), status[i % 3], font=get_font(14), fill=GRAY)
            y += 85
    
    create_phone_mockup(draw, left_x, phone_y, phone_width, phone_height, left_phone_content)
    
    def right_phone_content(d, sx, sy, sw, sh):
        # Payment options mockup
        font_small = get_font(18)
        d.text((sx + 20, sy + 50), "Payment Method", font=font_small, fill=WHITE)
        
        y = sy + 100
        options = [
            ("Full Cash", GREEN, "Pay complete amount"),
            ("Short Debt", YELLOW, "Pay part now"),
            ("Aqsat", PRIMARY, "Monthly installments"),
        ]
        
        for name, col, desc in options:
            d.rounded_rectangle([sx + 15, y, sx + sw - 15, y + 80], 12, (35, 35, 35))
            d.rounded_rectangle([sx + 25, y + 15, sx + 50, y + 40], 8, col)
            d.text((sx + 65, y + 15), name, font=font_small, fill=WHITE)
            d.text((sx + 65, y + 40), desc, font=get_font(14), fill=GRAY)
            y += 95
    
    create_phone_mockup(draw, right_x, phone_y, phone_width, phone_height, right_phone_content)
    
    # Step indicator
    if step_num and total_steps:
        font_small = get_font(24)
        step_text = f"Step {step_num}/{total_steps}"
        bbox = draw.textbbox((0, 0), step_text, font=font_small)
        text_width = bbox[2] - bbox[0]
        draw.text(((WIDTH - text_width)//2, HEIGHT - 60), step_text, font=font_small, fill=GRAY)
        
        # Progress bar
        bar_width = 400
        bar_height = 4
        bar_x = (WIDTH - bar_width) // 2
        bar_y = HEIGHT - 35
        draw.rounded_rectangle([bar_x, bar_y, bar_x + bar_width, bar_y + bar_height], 2, (50, 50, 50))
        progress = step_num / total_steps
        draw.rounded_rectangle([bar_x, bar_y, bar_x + int(bar_width * progress), bar_y + bar_height], 2, color)
    
    return img

def create_title_slide(title, subtitle=None, color=PRIMARY):
    """Create a title slide"""
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # Large glow effect
    for i in range(400, 0, -5):
        alpha = int(8 * (1 - i/400))
        r, g, b = color
        draw.ellipse([(WIDTH//2 - i, HEIGHT//2 - i//2),
                      (WIDTH//2 + i, HEIGHT//2 + i//2)],
                     fill=(r, g, b))
    
    # Smaller glow
    for i in range(200, 0, -3):
        r, g, b = color
        draw.ellipse([(WIDTH//2 - i, HEIGHT//2 - i//2),
                      (WIDTH//2 + i, HEIGHT//2 + i//2)],
                     fill=(r//2, g//2, b//2))
    
    # Title
    font_large = get_font(96, bold=True)
    bbox = draw.textbbox((0, 0), title, font=font_large)
    text_width = bbox[2] - bbox[0]
    x = (WIDTH - text_width) // 2
    draw.text((x, HEIGHT//2 - 50), title, font=font_large, fill=WHITE)
    
    # Subtitle
    if subtitle:
        font_medium = get_font(36)
        bbox = draw.textbbox((0, 0), subtitle, font=font_medium)
        text_width = bbox[2] - bbox[0]
        x = (WIDTH - text_width) // 2
        draw.text((x, HEIGHT//2 + 50), subtitle, font=font_medium, fill=GRAY)
    
    # Bottom decoration
    draw.rectangle([0, HEIGHT - 10, WIDTH, HEIGHT], fill=color)
    
    return img

def create_end_slide(text="Thank You", subtext="Questions? Contact your administrator"):
    """Create an ending slide"""
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # Gradient effect
    for i in range(HEIGHT//2, HEIGHT):
        ratio = (i - HEIGHT//2) / (HEIGHT//2)
        r = int(PRIMARY[0] * ratio)
        g = int(PRIMARY[1] * ratio)
        b = int(PRIMARY[2] * ratio)
        draw.line([(0, i), (WIDTH, i)], fill=(r, g, b))
    
    font_large = get_font(72, bold=True)
    font_medium = get_font(32)
    
    bbox = draw.textbbox((0, 0), text, font=font_large)
    text_width = bbox[2] - bbox[0]
    x = (WIDTH - text_width) // 2
    draw.text((x, HEIGHT//2 - 50), text, font=font_large, fill=WHITE)
    
    bbox = draw.textbbox((0, 0), subtext, font=font_medium)
    text_width = bbox[2] - bbox[0]
    x = (WIDTH - text_width) // 2
    draw.text((x, HEIGHT//2 + 30), subtext, font=font_medium, fill=GRAY)
    
    return img

def frames_to_video(frames, output_path, fps=FPS, duration_per_frame=3):
    """Convert frames to video using FFmpeg"""
    frame_dir = os.path.join(TEMP_DIR, 'frames')
    os.makedirs(frame_dir, exist_ok=True)
    
    # Calculate frames per image (duration * fps)
    frames_per_image = duration_per_frame * fps
    
    # Save frames - duplicate each frame for its duration
    frame_count = 0
    for frame in frames:
        for _ in range(frames_per_image):
            frame.save(os.path.join(frame_dir, f'frame_{frame_count:04d}.png'))
            frame_count += 1
    
    # Get frame count
    frame_pattern = os.path.join(frame_dir, 'frame_%04d.png')
    
    # FFmpeg command
    cmd = [
        'ffmpeg', '-y',
        '-framerate', str(fps),
        '-i', frame_pattern,
        '-c:v', 'libx264',
        '-preset', 'medium',
        '-crf', '23',
        '-pix_fmt', 'yuv420p',
        '-movflags', '+faststart',
        output_path
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"FFmpeg error: {result.stderr}")
        return False
    
    return True

def create_intro_video(output_path):
    """Create introduction video"""
    frames = []
    
    # Title
    frames.append(create_title_slide("Dental Clinic", "Patient Management System", PRIMARY))
    frames.append(create_title_slide("Welcome", "Easy booking, payment & treatment", BLUE))
    
    # Scene frames
    scenes = [
        ("Register Patient", "Add new patients with name, phone & medical history", 1, 4),
        ("Join Daily Queue", "Patients automatically added to today's queue", 2, 4),
        ("Doctor Reviews Treatment", "View medical history, allergies & start treatment", 3, 4),
        ("Easy Payment Options", "Full cash, short debt, or monthly installments", 4, 4),
    ]
    
    for title, text, step, total in scenes:
        frames.append(create_scene_frame("scene", text, None, step, total, PRIMARY))
    
    frames.append(create_end_slide())
    
    return frames_to_video(frames, output_path, duration_per_frame=3)

def create_patient_journey_video(output_path):
    """Create patient journey video"""
    frames = []
    
    # Title
    frames.append(create_title_slide("Patient Journey", "From arrival to payment", GREEN))
    
    # Step 1: Registration
    frames.append(create_scene_frame("scene", 
                                     "Step 1: Patient Registration",
                                     "Receptionist adds patient with name, phone, age & medical history",
                                     1, 5, PRIMARY))
    
    # Phone mockup showing patient form
    frames.append(create_app_frame("New Patient",
                                   [{"type": "input", "label": "Name"},
                                    {"type": "input", "label": "Phone"},
                                    {"type": "input", "label": "Age"},
                                    {"type": "button", "label": "Save"}],
                                   1, 5, PRIMARY))
    
    # Step 2: Queue
    frames.append(create_scene_frame("scene",
                                     "Step 2: Join Queue",
                                     "Patient automatically added to today's waiting list",
                                     2, 5, BLUE))
    
    frames.append(create_app_frame("Daily Queue",
                                   [{"name": "Ahmed", "status": "Waiting"},
                                    {"name": "Sara", "status": "In Treatment"}],
                                   2, 5, BLUE))
    
    # Step 3: Treatment
    frames.append(create_scene_frame("scene",
                                     "Step 3: Doctor Treatment",
                                     "Review history, chart teeth, add notes & x-rays",
                                     3, 5, PURPLE))
    
    # Step 4: Checkout options
    frames.append(create_scene_frame("scene",
                                     "Step 4: Checkout",
                                     "Three easy payment options",
                                     4, 5, GREEN))
    
    # Payment options visualization
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # Three payment cards
    card_width = 450
    card_height = 400
    gap = 60
    start_x = (WIDTH - (card_width * 3 + gap * 2)) // 2
    y = 250
    
    options = [
        ("Full Cash", "Pay complete amount", GREEN, "✓"),
        ("Short Debt", "Pay part now", YELLOW, "$"),
        ("Aqsat", "Monthly installments", PRIMARY, "📅"),
    ]
    
    for i, (title, desc, color, icon) in enumerate(options):
        x = start_x + i * (card_width + gap)
        draw_rounded_rect(draw, [x, y, x + card_width, y + card_height], 25, (40, 40, 40))
        draw_rounded_rect(draw, [x + 20, y + 30, x + card_width - 20, y + 100], 15, color)
        
        font_title = get_font(36, bold=True)
        font_body = get_font(24)
        
        bbox = draw.textbbox((0, 0), title, font=font_title)
        text_width = bbox[2] - bbox[0]
        draw.text((x + (card_width - text_width)//2, y + 130), title, font=font_title, fill=WHITE)
        
        bbox = draw.textbbox((0, 0), desc, font=font_body)
        text_width = bbox[2] - bbox[0]
        draw.text((x + (card_width - text_width)//2, y + 190), desc, font=font_body, fill=GRAY)
    
    frames.append(img)
    
    # Step 5: Aqsat detail
    frames.append(create_scene_frame("scene",
                                     "Aqsat: Pay Over Time",
                                     "For expensive treatments - down payment + monthly installments",
                                     5, 5, PRIMARY))
    
    # Aqsat example
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    font_title = get_font(48, bold=True)
    font_large = get_font(72, bold=True)
    font_medium = get_font(32)
    font_small = get_font(24)
    
    draw.text((WIDTH//2 - 200, 150), "Aqsat Example", font=font_title, fill=WHITE)
    
    # Treatment cost
    draw_rounded_rect(draw, [200, 250, 800, 400], 20, (40, 40, 40))
    draw.text((220, 270), "Treatment: Dental Implant", font=font_medium, fill=WHITE)
    draw.text((220, 320), "Total: 3,000,000 IQD", font=font_large, fill=PRIMARY)
    
    # Down payment
    draw_rounded_rect(draw, [900, 250, 1500, 400], 20, (40, 40, 40))
    draw.text((920, 270), "Down Payment", font=font_medium, fill=WHITE)
    draw.text((920, 320), "500,000 IQD", font=font_large, fill=GREEN)
    
    # Monthly
    draw_rounded_rect(draw, [1600, 250, 2200, 400], 20, (40, 40, 40))
    draw.text((1620, 270), "Monthly (5x)", font=font_medium, fill=WHITE)
    draw.text((1620, 320), "500,000 IQD", font=font_large, fill=BLUE)
    
    # Arrow
    draw.polygon([(850, 325), (880, 300), (880, 350)], fill=GRAY)
    draw.polygon([(1550, 325), (1520, 300), (1520, 350)], fill=GRAY)
    
    frames.append(img)
    
    frames.append(create_end_slide("Patient Ready!", "WhatsApp reminders sent automatically"))
    
    return frames_to_video(frames, output_path, duration_per_frame=4)

def create_receptionist_video(output_path):
    """Create receptionist training video"""
    frames = []
    
    frames.append(create_title_slide("Receptionist", "Quick Start Guide", BLUE))
    
    # Section 1: Queue
    frames.append(create_scene_frame("section", "Managing Daily Queue", None, None, None, BLUE))
    
    queue_steps = [
        ("Open Queue", "Morning start - check today's list"),
        ("Add Walk-In", "Search or quick-add patient"),
        ("Start Treatment", "Mark patient as active"),
        ("Complete", "Mark done after checkout"),
    ]
    
    for i, (title, desc) in enumerate(queue_steps, 1):
        frames.append(create_scene_frame("scene", title, desc, i, 4, BLUE))
    
    # Section 2: Registration
    frames.append(create_scene_frame("section", "Patient Registration", None, None, None, GREEN))
    
    reg_steps = [
        ("Click Patients", "Go to Patients menu"),
        ("New Patient", "Click the add button"),
        ("Fill Details", "Name, phone, age, gender"),
        ("Save", "Patient added to system"),
    ]
    
    for i, (title, desc) in enumerate(reg_steps, 1):
        frames.append(create_scene_frame("scene", title, desc, i, 4, GREEN))
    
    frames.append(create_end_slide("Ready to Work!", "Call admin for help"))
    
    return frames_to_video(frames, output_path, duration_per_frame=3)

def create_aqsat_video(output_path):
    """Create Aqsat installment system video"""
    frames = []
    
    frames.append(create_title_slide("Aqsat System", "Clinic Financing Explained", PRIMARY))
    
    # What is Aqsat
    frames.append(create_scene_frame("scene",
                                     "What is Aqsat?",
                                     "Our clinic financing - pay expensive treatments in monthly installments",
                                     1, 4, PRIMARY))
    
    # How it works
    frames.append(create_scene_frame("scene",
                                     "How Aqsat Works",
                                     "Patient pays down payment + monthly installments over time",
                                     2, 4, PRIMARY))
    
    # Example calculation
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    font_title = get_font(56, bold=True)
    font_large = get_font(64, bold=True)
    font_medium = get_font(32)
    font_small = get_font(24)
    
    draw.text((100, 80), "Example: 3,000,000 IQD Implant", font=font_title, fill=WHITE)
    
    # Visual breakdown
    items = [
        ("Total Cost", "3,000,000", PRIMARY),
        ("Down Payment", "500,000", GREEN),
        ("Remaining", "2,500,000", YELLOW),
        ("6 Months", "416,666/mo", BLUE),
    ]
    
    for i, (label, value, color) in enumerate(items):
        x = 150 + i * 420
        y = 250
        draw_rounded_rect(draw, [x, y, x + 380, y + 200], 20, (40, 40, 40))
        draw.text((x + 30, y + 30), label, font=font_small, fill=GRAY)
        draw.text((x + 30, y + 90), value, font=font_large, fill=color)
        draw.text((x + 30, y + 150), "IQD", font=font_small, fill=GRAY)
    
    # Timeline visual
    y_line = 600
    draw.line([(200, y_line), (1700, y_line)], fill=GRAY, width=3)
    
    for i in range(7):
        x = 200 + i * 250
        draw.ellipse([x - 8, y_line - 8, x + 8, y_line + 8], fill=PRIMARY if i > 0 else GREEN)
        if i > 0:
            draw.text((x - 30, y_line + 30), f"Month {i}", font=font_small, fill=GRAY)
    
    frames.append(img)
    
    # Reminders
    frames.append(create_scene_frame("scene",
                                     "Automatic Reminders",
                                     "WhatsApp message sent before each payment due date",
                                     3, 4, PRIMARY))
    
    # Show WhatsApp mockup
    img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
    draw = ImageDraw.Draw(img)
    
    # WhatsApp style message
    draw_rounded_rect(draw, [400, 200, 1500, 700], 30, (45, 45, 45))
    draw.text((450, 240), "📱 WhatsApp Reminder", font=get_font(28), fill=GREEN)
    
    draw_rounded_rect(draw, [450, 290, 1200, 400], 20, (55, 55, 55))
    draw.text((480, 320), "Assalamu Alaikum!", font=get_font(24), fill=WHITE)
    draw.text((480, 360), "Your installment of", font=get_font(22), fill=GRAY)
    draw.text((480, 395), "500,000 IQD", font=get_font(36, bold=True), fill=PRIMARY)
    draw.text((480, 450), "is due on March 15th", font=get_font(22), fill=GRAY)
    draw.text((480, 490), "Tap to pay: [LINK]", font=get_font(22), fill=BLUE)
    
    frames.append(img)
    
    # Tracking
    frames.append(create_scene_frame("scene",
                                     "Track Everything",
                                     "View active plans, overdue payments, collected amounts",
                                     4, 4, PRIMARY))
    
    frames.append(create_end_slide("Financing Made Easy!", "More patients, better care"))
    
    return frames_to_video(frames, output_path, duration_per_frame=4)

def create_tips_video(output_path):
    """Create quick tips video"""
    frames = []
    
    frames.append(create_title_slide("Quick Tips", "10 Things to Know", YELLOW))
    
    tips = [
        ("Check Allergies", "Always review before prescribing", PRIMARY),
        ("Quick Add", "Use for fast patient registration", BLUE),
        ("Chart Teeth", "Tap to mark - saves automatically", GREEN),
        ("Send Receipts", "WhatsApp - no need to print", PURPLE),
        ("Short Debt", "For small remaining balances", YELLOW),
        ("Aqsat", "For large treatments over time", PRIMARY),
        ("Calendar", "Book follow-ups in advance", BLUE),
        ("Search by Phone", "Faster than searching by name", GREEN),
        ("Dark Mode", "Available in Settings", PURPLE),
        ("Get Help", "Contact admin for issues", YELLOW),
    ]
    
    for i, (title, desc, color) in enumerate(tips, 1):
        img = Image.new('RGB', (WIDTH, HEIGHT), DARK)
        draw = ImageDraw.Draw(img)
        
        # Number circle
        draw.ellipse([WIDTH//2 - 60, 200, WIDTH//2 + 60, 320], fill=color)
        font_num = get_font(72, bold=True)
        bbox = draw.textbbox((0, 0), str(i), font=font_num)
        text_width = bbox[2] - bbox[0]
        draw.text(((WIDTH - text_width)//2, 220), str(i), font=font_num, fill=WHITE)
        
        # Title and description
        font_title = get_font(56, bold=True)
        font_body = get_font(32)
        
        bbox = draw.textbbox((0, 0), title, font=font_title)
        text_width = bbox[2] - bbox[0]
        draw.text(((WIDTH - text_width)//2, 400), title, font=font_title, fill=WHITE)
        
        bbox = draw.textbbox((0, 0), desc, font=font_body)
        text_width = bbox[2] - bbox[0]
        draw.text(((WIDTH - text_width)//2, 500), desc, font=font_body, fill=GRAY)
        
        # Progress dots
        for j in range(10):
            x = WIDTH//2 - 45 + j * 10
            draw.ellipse([x, HEIGHT - 80, x + 6, HEIGHT - 74], 
                        fill=color if j == i-1 else (50, 50, 50))
        
        frames.append(img)
    
    frames.append(create_end_slide("You're Ready!", "Start using the app"))
    
    return frames_to_video(frames, output_path, duration_per_frame=2)

def main():
    """Generate all videos"""
    output_dir = os.path.join(os.path.dirname(__file__), 'videos')
    os.makedirs(output_dir, exist_ok=True)
    
    print("🎬 Dental Clinic Video Generator")
    print("=" * 40)
    
    videos = [
        ("1_intro.mp4", create_intro_video, "Introduction"),
        ("2_patient_journey.mp4", create_patient_journey_video, "Patient Journey"),
        ("3_receptionist.mp4", create_receptionist_video, "Receptionist Training"),
        ("4_aqsat.mp4", create_aqsat_video, "Aqsat System"),
        ("5_tips.mp4", create_tips_video, "Quick Tips"),
    ]
    
    for filename, creator, name in videos:
        output_path = os.path.join(output_dir, filename)
        print(f"\n📹 Creating: {name}...")
        print(f"   Output: {output_path}")
        
        if creator(output_path):
            print(f"   ✅ Done!")
        else:
            print(f"   ❌ Failed!")
    
    print(f"\n✨ All videos saved to: {output_dir}")
    print("\nFiles created:")
    for filename, _, name in videos:
        filepath = os.path.join(output_dir, filename)
        if os.path.exists(filepath):
            size_mb = os.path.getsize(filepath) / (1024 * 1024)
            print(f"  • {filename} ({size_mb:.1f} MB)")

if __name__ == "__main__":
    main()
