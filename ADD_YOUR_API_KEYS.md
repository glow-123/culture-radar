# 🔑 How to Add Your API Keys to Culture Radar

## Quick Setup (2 minutes)

### Step 1: Open the `.env` file
Located at: `/root/culture-radar/.env`

### Step 2: Replace the placeholders with your actual API keys

Find these lines and replace with your keys:
```env
# Replace THIS:
OPENAGENDA_API_KEY=YOUR_OPENAGENDA_KEY_HERE

# With your actual key:
OPENAGENDA_API_KEY=abc123your-real-key-here
```

### Step 3: Save the file

### Step 4: Test your APIs
Run this command:
```bash
php setup-apis.php
```

## Your API Keys Should Look Like:

### OpenAgenda
```env
OPENAGENDA_API_KEY=5a7b9c2d4e6f8g1h3i5j7k9l
```

### OpenWeatherMap
```env
OPENWEATHERMAP_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### Google Maps
```env
GOOGLE_MAPS_API_KEY=AIzaSyB1234567890abcdefghijklmnop
```

### Mapbox (optional)
```env
MAPBOX_API_KEY=pk.eyJ1IjoieW91cnVzZXJuYW1lIiwiYSI6ImNrMTIzNDU2Nzg5MGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6In0.abc123
```

## Example Complete .env File:

```env
# Culture Radar Environment Configuration
APP_NAME="Culture Radar"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8888

# Database Configuration
DB_HOST=localhost
DB_PORT=8889
DB_NAME=culture_radar
DB_USER=root
DB_PASS=root

# Your Real API Keys
OPENAGENDA_API_KEY=5a7b9c2d4e6f8g1h3i5j7k9l
OPENWEATHERMAP_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
GOOGLE_MAPS_API_KEY=AIzaSyB1234567890abcdefghijklmnop
MAPBOX_API_KEY=pk.eyJ1IjoieW91cnVzZXJuYW1lIiwiYSI6ImNrMTIzNDU2Nzg5MGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6In0.abc123
```

## After Adding Your Keys:

1. **Test the APIs:**
   ```bash
   php setup-apis.php
   ```
   This will:
   - Verify each API key works
   - Import real events from OpenAgenda
   - Test weather data
   - Check maps functionality

2. **Check the Dashboard:**
   - Visit: http://localhost:8888/dashboard.php
   - You should now see:
     - Real weather data (not "--°C")
     - Actual events from OpenAgenda
     - Working maps
     - Location detection

3. **If Something Doesn't Work:**
   - Check the test output for errors
   - Make sure keys are copied correctly (no extra spaces)
   - Verify your API keys are active on their platforms

## API Key Sources:

Don't have the keys yet? Get them here:

1. **OpenAgenda** (Free)
   - Go to: https://openagenda.com/
   - Sign up for free account
   - Go to: https://openagenda.com/settings/api
   - Copy your API key

2. **OpenWeatherMap** (Free)
   - Go to: https://openweathermap.org/api
   - Sign up for free account
   - Go to: https://home.openweathermap.org/api_keys
   - Copy your API key

3. **Google Maps** (Free $200 credit/month)
   - Go to: https://console.cloud.google.com/
   - Create new project
   - Enable Maps JavaScript API
   - Create credentials → API Key
   - Copy your API key

4. **Mapbox** (Free 50k requests/month)
   - Go to: https://account.mapbox.com/auth/signup/
   - Sign up for free account
   - Go to: https://account.mapbox.com/access-tokens/
   - Copy your default public token

## Troubleshooting:

### "API key invalid"
- Double-check you copied the entire key
- Make sure there are no quotes around the key in .env
- Verify the key is activated on the API platform

### "No events showing"
- OpenAgenda key might be wrong
- Run `php setup-apis.php` to import events
- Check if your OpenAgenda account has access to public agendas

### "Weather not working"
- OpenWeatherMap key may need 10 minutes to activate after creation
- Make sure you're using the correct API key (not the API secret)

## Success Checklist:

- [ ] Added OpenAgenda API key
- [ ] Added OpenWeatherMap API key  
- [ ] Added Google Maps API key
- [ ] Ran `php setup-apis.php`
- [ ] Dashboard shows real weather
- [ ] Dashboard shows real events
- [ ] Maps are loading

Once all checked, your Culture Radar is fully operational! 🎉