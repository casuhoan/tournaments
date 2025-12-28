FROM node:20

WORKDIR /app

# Install app dependencies
COPY package*.json ./
RUN npm install

# Bundle app source
# Bundle app source
COPY . .

# Copy data to temp folder for auto-migration
COPY data /tempdata

# Expose port
EXPOSE 8000

# Start command
CMD ["npm", "start"]
