# Use an official lightweight Python image
FROM python:3.9-slim

# Set the working directory inside the container
WORKDIR /app

# Copy the requirements file into the container
COPY requirements.txt .

# Install the Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy your ticker script and the cache directory into the container
# The cache directory is copied to ensure the script can start up
# even if it's restarted without network access on the first run.
COPY . .

# Command to run your application
CMD ["python", "tick_streamer.py"]