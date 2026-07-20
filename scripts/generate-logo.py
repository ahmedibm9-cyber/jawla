import os
import base64
import google.genai as genai
from google.genai import types

api_key = os.environ.get('GEMINI_API_KEY')
if not api_key:
    print("No GEMINI_API_KEY set")
    exit(1)

client = genai.Client(api_key=api_key)

prompt = (
    "Generate a professional, modern app logo for Jawla, a field sales management app. "
    "The logo should feature a stylized route/map path with waypoint dots representing a sales journey. "
    "Use emerald green (#059669) as the primary color with a gradient to lighter green. "
    "Include an amber/gold (#D97706) accent dot as the starting waypoint. "
    "The path should curve elegantly between waypoints. "
    "Clean, minimalist, geometric style on a white background. "
    "The mark should work as a standalone icon with no text. "
    "Think modern SaaS app icon with rounded corners, professional. "
    "The path represents a sales rep daily route visiting customers."
)

response = client.models.generate_images(
    model='imagen-4.0-fast-generate-001',
    prompt=prompt,
    config=types.GenerateImagesConfig(
        number_of_images=1,
    )
)

for i, image in enumerate(response.generated_images):
    output_path = f'C:/projects/jawla/public/images/logo-ai-generated.png'
    image.image.save(output_path)
    print(f'Logo saved to {output_path}')
